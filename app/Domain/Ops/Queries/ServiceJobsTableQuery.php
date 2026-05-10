<?php

namespace App\Domain\Ops\Queries;

use App\Models\Operations\ServiceJob;
use App\Support\Ops\JobStatusPresenter;
use App\Support\Ops\OpsUiPresenter;
use App\Support\Ops\SlaLabelPresenter;
use Illuminate\Database\Eloquent\Builder;

class ServiceJobsTableQuery
{
    public function builder(array $filters = [], ?string $organizationId = null): Builder
    {
        $organizationScope = $this->resolveOrganizationScope($organizationId);

        $query = ServiceJob::query()
            ->with([
                'executor:id,name,display_name,status',
                'currentAssignment:id,service_job_id,executor_id,status,eta_at',
                'currentAssignment.executor:id,name,display_name,status',
                'slaTimers:id,service_job_id,status,dispatch_state,arrival_state,completion_state',
            ])
            ->withCount(['exceptions as exceptions_count' => function (Builder $q): void {
                $q->whereIn('status', ['open', 'acknowledged', 'investigating', 'mitigated']);
            }]);

        if ($organizationScope !== null) {
            $query->where('organization_id', $organizationScope);
        }

        if (! empty($filters['domain'])) {
            $query->where('service_domain', $filters['domain']);
        }

        if (! empty($filters['zone'])) {
            $zone = $filters['zone'];
            $query->where(function (Builder $q) use ($zone): void {
                $q->where('geo_zone_id', $zone)->orWhere('zone_id', $zone);
            });
        }

        if (! empty($filters['status'])) {
            $query->where('status', JobStatusPresenter::normalize((string) $filters['status']));
        }

        if (! empty($filters['exceptions_only'])) {
            $query->whereHas('exceptions', function (Builder $q): void {
                $q->whereIn('status', ['open', 'acknowledged', 'investigating', 'mitigated']);
            });
        }

        if (! empty($filters['at_risk_only'])) {
            $query->where(function (Builder $q): void {
                $q->whereHas('slaTimers', function (Builder $timerQ): void {
                    $timerQ
                        ->whereIn('status', ['warning', 'breached'])
                        ->orWhereIn('dispatch_state', ['warning', 'breached'])
                        ->orWhereIn('arrival_state', ['warning', 'breached'])
                        ->orWhereIn('completion_state', ['warning', 'breached']);
                })->orWhereHas('exceptions', function (Builder $exQ): void {
                    $exQ->whereIn('status', ['open', 'acknowledged', 'investigating', 'mitigated']);
                });
            });
        }

        return $query->orderByDesc('updated_at');
    }

    private function resolveOrganizationScope(?string $organizationId): ?string
    {
        if ($organizationId !== null && $organizationId !== '') {
            return $organizationId;
        }

        $user = auth()->user();
        $fromUser = (string) ($user?->organization_id ?? '');
        if ($fromUser !== '') {
            return $fromUser;
        }

        $fromDefault = (string) ($user?->default_org_id ?? '');
        return $fromDefault !== '' ? $fromDefault : null;
    }

    public function mapRow(ServiceJob $job): array
    {
        $slaState = SlaLabelPresenter::stateForJob($job);
        $exceptionsCount = (int) ($job->exceptions_count ?? 0);
        $executorName = $job->executor?->display_name
            ?: $job->executor?->name
            ?: $job->currentAssignment?->executor?->display_name
            ?: $job->currentAssignment?->executor?->name;

        return [
            'id' => $job->id,
            'domain' => $job->service_domain,
            'kind' => $job->job_kind ?: $job->job_type,
            'priority' => (string) $job->priority,
            'status' => JobStatusPresenter::normalize($job->status),
            'status_label' => JobStatusPresenter::label($job->status),
            'executor' => $executorName,
            'eta' => OpsUiPresenter::etaForJob($job),
            'sla_state' => $slaState,
            'sla_label' => SlaLabelPresenter::label($slaState),
            'risk_score' => OpsUiPresenter::riskScore($job, $exceptionsCount, $slaState),
            'exceptions_count' => $exceptionsCount,
            'updated_at' => optional($job->updated_at)->toIso8601String(),
            'coordinates' => [
                'service' => ['lat' => $job->service_lat, 'lng' => $job->service_lng],
                'pickup' => ['lat' => $job->pickup_lat, 'lng' => $job->pickup_lng],
                'dropoff' => ['lat' => $job->dropoff_lat, 'lng' => $job->dropoff_lng],
            ],
        ];
    }
}

