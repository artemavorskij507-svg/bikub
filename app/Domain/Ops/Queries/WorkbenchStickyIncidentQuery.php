<?php

namespace App\Domain\Ops\Queries;

use App\Domain\Exceptions\Models\OperationException;
use App\Domain\Moving\Models\TeamAssignment;
use App\Domain\Operations\Models\Executor;
use App\Domain\Operations\Models\ServiceJob;
use App\Models\Operations\Assignment;

class WorkbenchStickyIncidentQuery
{
    public function execute(string $organizationId): array
    {
        $organizationScope = (string) $organizationId;
        $activeStatuses = ['pending_dispatch', 'assigned', 'en_route', 'arrived', 'in_progress'];
        $openExceptionStatuses = ['open', 'acknowledged', 'investigating', 'mitigated'];

        $emergencyQuery = ServiceJob::query()
            ->where('organization_id', $organizationScope)
            ->where('service_domain', 'roadside')
            ->whereIn('status', $activeStatuses)
            ->where(function ($query): void {
                $query->whereIn('priority', ['emergency', 'urgent'])
                    ->orWhereJsonContains('metadata->is_emergency', true);
            });
        $emergencyRoadsideCount = (clone $emergencyQuery)->count();
        $emergencyRoadsideFocusJobId = (clone $emergencyQuery)->latest('updated_at')->value('id');

        $slaBreachQuery = ServiceJob::query()
            ->where('organization_id', $organizationScope)
            ->whereIn('status', $activeStatuses)
            ->whereHas('slaTimers', fn ($query) => $query->where('status', 'breached'));
        $slaBreachedCount = (clone $slaBreachQuery)->count();
        $slaBreachedFocusJobId = (clone $slaBreachQuery)->latest('updated_at')->value('id');

        $waitingAcceptanceQuery = Assignment::query()
            ->where('organization_id', $organizationScope)
            ->whereIn('status', ['proposed', 'offered'])
            ->whereNotNull('acceptance_deadline_at');
        $waitingAcceptanceCount = (clone $waitingAcceptanceQuery)->count();
        $waitingAcceptanceFocusJobId = (clone $waitingAcceptanceQuery)->latest('updated_at')->value('service_job_id');

        $noExecutorQuery = OperationException::query()
            ->where('organization_id', $organizationScope)
            ->whereIn('status', $openExceptionStatuses)
            ->where('type', 'no_executor_found');
        $noExecutorFoundCount = (clone $noExecutorQuery)->count();
        $noExecutorFocusJobId = (clone $noExecutorQuery)->latest('detected_at')->value('service_job_id');

        $staleGpsCriticalCount = Executor::query()
            ->where('organization_id', $organizationScope)
            ->whereIn('status', ['available', 'busy'])
            ->where(function ($query): void {
                $query
                    ->where('last_seen_at', '<=', now()->subMinutes(15))
                    ->orWhere(function ($sub): void {
                        $sub->whereNull('last_seen_at')
                            ->where('updated_at', '<=', now()->subMinutes(15));
                    });
            })
            ->count();

        $movingCandidates = ServiceJob::query()
            ->where('organization_id', $organizationScope)
            ->where('service_domain', 'moving')
            ->whereIn('status', $activeStatuses)
            ->get(['id', 'required_team_size']);

        $movingIncompleteCount = 0;
        $movingFocusJobId = null;
        foreach ($movingCandidates as $job) {
            $required = (int) ($job->required_team_size ?? 0);
            if ($required <= 1) {
                continue;
            }

            $latestTeam = TeamAssignment::query()
                ->where('service_job_id', $job->id)
                ->latest('id')
                ->first(['member_executor_ids_json']);

            $members = (array) ($latestTeam?->member_executor_ids_json ?? []);
            if (count($members) < $required) {
                $movingIncompleteCount++;
                $movingFocusJobId ??= (int) $job->id;
            }
        }

        $items = [
            [
                'key' => 'roadside_emergency',
                'label' => 'Roadside emergency',
                'count' => $emergencyRoadsideCount,
                'severity' => 'danger',
                'sticky' => $emergencyRoadsideCount > 0,
                'description' => 'Emergency roadside jobs awaiting immediate attention.',
                'filter' => ['domain' => 'roadside', 'priority' => 'emergency'],
                'focus_job_id' => $emergencyRoadsideFocusJobId ? (int) $emergencyRoadsideFocusJobId : null,
            ],
            [
                'key' => 'sla_breached',
                'label' => 'SLA breached',
                'count' => $slaBreachedCount,
                'severity' => 'danger',
                'sticky' => $slaBreachedCount > 0,
                'description' => 'Active jobs with breached SLA timers.',
                'filter' => ['at_risk_only' => true],
                'focus_job_id' => $slaBreachedFocusJobId ? (int) $slaBreachedFocusJobId : null,
            ],
            [
                'key' => 'waiting_acceptance',
                'label' => 'Waiting for acceptance',
                'count' => $waitingAcceptanceCount,
                'severity' => 'warning',
                'sticky' => $waitingAcceptanceCount > 0,
                'description' => 'Assignments awaiting executor response.',
                'filter' => ['status' => 'assigned'],
                'focus_job_id' => $waitingAcceptanceFocusJobId ? (int) $waitingAcceptanceFocusJobId : null,
            ],
            [
                'key' => 'no_executor_found',
                'label' => 'No executor found',
                'count' => $noExecutorFoundCount,
                'severity' => 'warning',
                'sticky' => $noExecutorFoundCount > 0,
                'description' => 'Open exceptions where dispatch found no capable executor.',
                'filter' => ['exceptions_only' => true],
                'focus_job_id' => $noExecutorFocusJobId ? (int) $noExecutorFocusJobId : null,
            ],
            [
                'key' => 'stale_gps_critical',
                'label' => 'Stale GPS critical',
                'count' => $staleGpsCriticalCount,
                'severity' => 'warning',
                'sticky' => $staleGpsCriticalCount > 0,
                'description' => 'Dispatchable executors with stale or missing telemetry.',
                'filter' => ['executors_only' => true],
                'focus_job_id' => null,
            ],
            [
                'key' => 'moving_team_incomplete',
                'label' => 'Moving team incomplete',
                'count' => $movingIncompleteCount,
                'severity' => 'info',
                'sticky' => $movingIncompleteCount > 0,
                'description' => 'Moving jobs without required team size.',
                'filter' => ['domain' => 'moving'],
                'focus_job_id' => $movingFocusJobId,
            ],
        ];

        return [
            'generated_at' => now()->toIso8601String(),
            'items' => $items,
            'sticky_count' => collect($items)->where('sticky', true)->count(),
            'has_sticky' => collect($items)->contains(fn (array $item): bool => (bool) ($item['sticky'] ?? false)),
        ];
    }
}
