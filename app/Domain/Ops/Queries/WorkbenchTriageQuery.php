<?php

namespace App\Domain\Ops\Queries;

use App\Domain\Exceptions\Models\OperationException;
use App\Domain\Moving\Models\TeamAssignment;
use App\Domain\Operations\Models\Executor;
use App\Domain\Operations\Models\ServiceJob;
use App\Models\Operations\Assignment;

class WorkbenchTriageQuery
{
    public function execute(string $organizationId): array
    {
        $orgId = $organizationId;
        $openExceptionStatuses = ['open', 'acknowledged', 'investigating', 'mitigated'];

        $emergencyRoadside = ServiceJob::query()
            ->where('organization_id', $orgId)
            ->where('service_domain', 'roadside')
            ->where(function ($query): void {
                $query->whereIn('priority', ['emergency', 'urgent'])
                    ->orWhereJsonContains('metadata->is_emergency', true);
            })
            ->whereIn('status', ['pending_dispatch', 'assigned', 'en_route', 'arrived', 'in_progress'])
            ->count();

        $slaBreached = ServiceJob::query()
            ->where('organization_id', $orgId)
            ->whereHas('slaTimers', fn ($query) => $query->where('status', 'breached'))
            ->whereIn('status', ['pending_dispatch', 'assigned', 'en_route', 'arrived', 'in_progress'])
            ->count();

        $waitingResponse = Assignment::query()
            ->where('organization_id', $orgId)
            ->whereIn('status', ['proposed', 'accepted'])
            ->whereNotNull('acceptance_deadline_at')
            ->where('acceptance_deadline_at', '>', now())
            ->count();

        $noExecutorFound = OperationException::query()
            ->where('organization_id', $orgId)
            ->whereIn('status', $openExceptionStatuses)
            ->where('type', 'no_executor_found')
            ->count();

        $staleGps = Executor::query()
            ->where('organization_id', $orgId)
            ->whereIn('status', ['available', 'busy'])
            ->where('updated_at', '<=', now()->subMinutes(10))
            ->count();

        $movingIncomplete = ServiceJob::query()
            ->where('organization_id', $orgId)
            ->where('service_domain', 'moving')
            ->whereIn('status', ['pending_dispatch', 'assigned', 'en_route', 'arrived', 'in_progress'])
            ->get(['id', 'required_team_size'])
            ->filter(function ($job): bool {
                $required = (int) ($job->required_team_size ?? 0);
                if ($required <= 1) {
                    return false;
                }

                $latestTeam = TeamAssignment::query()
                    ->where('service_job_id', $job->id)
                    ->latest('id')
                    ->first(['member_executor_ids_json']);
                $members = (array) ($latestTeam?->member_executor_ids_json ?? []);

                return count($members) < $required;
            })
            ->count();

        return [
            'cards' => [
                [
                    'key' => 'emergency_roadside',
                    'label' => 'Emergency roadside',
                    'count' => $emergencyRoadside,
                    'severity' => 'danger',
                    'filter' => ['domain' => 'roadside', 'priority' => 'emergency'],
                ],
                [
                    'key' => 'sla_breached',
                    'label' => 'SLA breached',
                    'count' => $slaBreached,
                    'severity' => 'danger',
                    'filter' => ['at_risk_only' => true],
                ],
                [
                    'key' => 'waiting_response',
                    'label' => 'Waiting response',
                    'count' => $waitingResponse,
                    'severity' => 'warning',
                    'filter' => ['status' => 'assigned'],
                ],
                [
                    'key' => 'no_executor_found',
                    'label' => 'No executor found',
                    'count' => $noExecutorFound,
                    'severity' => 'warning',
                    'filter' => ['exceptions_only' => true],
                ],
                [
                    'key' => 'stale_gps',
                    'label' => 'Stale GPS',
                    'count' => $staleGps,
                    'severity' => 'info',
                    'filter' => ['executors_only' => true],
                ],
                [
                    'key' => 'moving_team_incomplete',
                    'label' => 'Moving team incomplete',
                    'count' => $movingIncomplete,
                    'severity' => 'info',
                    'filter' => ['domain' => 'moving'],
                ],
            ],
        ];
    }
}
