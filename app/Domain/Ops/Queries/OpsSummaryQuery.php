<?php

namespace App\Domain\Ops\Queries;

use App\Models\Operations\Assignment;
use App\Models\Operations\Executor;
use App\Support\Ops\ExecutorStatusPresenter;

class OpsSummaryQuery
{
    public function __construct(
        private readonly ServiceJobsTableQuery $jobsTableQuery,
        private readonly OperationExceptionsTableQuery $exceptionsTableQuery,
    ) {}

    public function execute(array $filters = [], ?string $organizationId = null): array
    {
        $organizationScope = $this->resolveOrganizationScope($organizationId);

        $jobsBuilder = $this->jobsTableQuery->builder($filters, $organizationScope);
        $jobs = (clone $jobsBuilder)->limit(500)->get();

        $mappedJobs = $jobs->map(fn ($job) => $this->jobsTableQuery->mapRow($job));

        $activeJobs = $mappedJobs->whereIn('status', ['assigned', 'en_route', 'arrived', 'in_progress'])->count();
        $pendingDispatch = $mappedJobs->where('status', 'pending_dispatch')->count();
        $assigned = $mappedJobs->where('status', 'assigned')->count();
        $inProgress = $mappedJobs->where('status', 'in_progress')->count();
        $atRisk = $mappedJobs->where('risk_score', '>=', 60)->count();

        $openExceptions = $this->exceptionsTableQuery->builder($filters, $organizationScope)
            ->whereIn('status', ['open', 'acknowledged', 'investigating', 'mitigated'])
            ->count();

        $avgDispatchTime = Assignment::query()
            ->when($organizationScope !== null, fn ($q) => $q->where('organization_id', $organizationScope))
            ->whereNotNull('accepted_at')
            ->selectRaw('AVG(EXTRACT(EPOCH FROM (accepted_at - created_at)) / 60) as avg_minutes')
            ->value('avg_minutes');

        $avgArrivalDelay = Assignment::query()
            ->when($organizationScope !== null, fn ($q) => $q->where('organization_id', $organizationScope))
            ->whereNotNull('arrived_at')
            ->whereNotNull('arrival_deadline_at')
            ->selectRaw('AVG(GREATEST(EXTRACT(EPOCH FROM (arrived_at - arrival_deadline_at)) / 60, 0)) as avg_delay')
            ->value('avg_delay');

        $dispatchPressureByDomain = $mappedJobs
            ->groupBy('domain')
            ->map(fn ($items, $domain) => [
                'domain' => $domain,
                'count' => $items->count(),
            ])
            ->values()
            ->all();

        $executorsAvailability = Executor::query()
            ->when($organizationScope !== null, fn ($q) => $q->where('organization_id', $organizationScope))
            ->whereIn('status', ['offline', 'available', 'busy', 'paused', 'suspended', 'online', 'idle'])
            ->get(['status'])
            ->groupBy(fn ($e) => ExecutorStatusPresenter::normalize($e->status))
            ->map(fn ($group, $status) => [
                'status' => $status,
                'count' => $group->count(),
            ])
            ->values()
            ->all();

        $recentReassignments = Assignment::query()
            ->when($organizationScope !== null, fn ($q) => $q->where('organization_id', $organizationScope))
            ->where('status', 'reassigned')
            ->latest('updated_at')
            ->limit(10)
            ->get(['id', 'service_job_id', 'executor_id', 'updated_at'])
            ->map(fn ($a) => [
                'assignment_id' => $a->id,
                'job_id' => $a->service_job_id,
                'executor_id' => $a->executor_id,
                'at' => optional($a->updated_at)->toIso8601String(),
            ])
            ->all();

        $availableExecutors = collect($executorsAvailability)->firstWhere('status', 'available')['count'] ?? 0;
        $activeExecutors = collect($executorsAvailability)
            ->whereIn('status', ['available', 'busy'])
            ->sum('count');
        $staleExecutors = Executor::query()
            ->when($organizationScope !== null, fn ($q) => $q->where('organization_id', $organizationScope))
            ->whereNotNull('last_seen_at')
            ->where('last_seen_at', '<=', now()->subMinutes(5))
            ->count();

        return [
            'kpi' => [
                'total_jobs' => $mappedJobs->count(),
                'active_jobs' => $activeJobs,
                'pending_dispatch' => $pendingDispatch,
                'pending_dispatch_jobs' => $pendingDispatch,
                'assigned' => $assigned,
                'assigned_jobs' => $assigned,
                'in_progress' => $inProgress,
                'in_progress_jobs' => $inProgress,
                'at_risk' => $atRisk,
                'at_risk_jobs' => $atRisk,
                'open_exceptions' => $openExceptions,
                'active_executors' => $activeExecutors,
                'available_executors' => (int) $availableExecutors,
                'stale_executors' => $staleExecutors,
                'avg_dispatch_time' => round((float) ($avgDispatchTime ?? 0), 1),
                'avg_arrival_delay' => round((float) ($avgArrivalDelay ?? 0), 1),
            ],
            'at_risk_jobs' => $mappedJobs->where('risk_score', '>=', 60)->take(10)->values()->all(),
            'open_exceptions' => $this->exceptionsTableQuery->builder($filters, $organizationScope)
                ->whereIn('status', ['open', 'acknowledged', 'investigating', 'mitigated'])
                ->limit(10)
                ->get()
                ->map(fn ($e) => $this->exceptionsTableQuery->mapRow($e))
                ->values()
                ->all(),
            'dispatch_pressure_by_domain' => $dispatchPressureByDomain,
            'executors_availability' => $executorsAvailability,
            'recent_reassignments' => $recentReassignments,
        ];
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
}

