<?php

namespace App\Domain\Ops\Queries;

use App\Models\Operations\Executor;
use App\Support\Ops\ExecutorStatusPresenter;
use Carbon\Carbon;
use Illuminate\Support\Facades\Redis;

class LiveOperationsMapQuery
{
    public function __construct(
        private readonly ServiceJobsTableQuery $jobsQuery,
        private readonly OperationExceptionsTableQuery $exceptionsQuery,
    ) {}

    public function execute(array $filters = [], ?string $organizationId = null): array
    {
        $organizationScope = $this->resolveOrganizationScope($organizationId);

        $jobs = $this->jobsQuery->builder($filters, $organizationScope)
            ->whereIn('status', ['pending_dispatch', 'assigned', 'en_route', 'arrived', 'in_progress'])
            ->limit(300)
            ->get()
            ->map(fn ($job) => $this->jobsQuery->mapRow($job))
            ->values();

        $executors = Executor::query()
            ->when(! empty($filters['domain']), function ($q): void {
                // kept for interface parity; executors are not domain-bound in schema
            })
            ->when($organizationScope !== null, fn ($q) => $q->where('organization_id', $organizationScope))
            ->whereIn('status', ['offline', 'available', 'busy', 'paused', 'suspended', 'online', 'idle'])
            ->limit(300)
            ->get()
            ->map(function (Executor $executor): array {
                $rawLocation = Redis::get("executor:{$executor->id}:last_location");
                $lastLocation = $rawLocation ? json_decode($rawLocation, true) : null;
                $lastSeenRaw = Redis::get("executor:{$executor->id}:last_seen_at");
                $lastSeenAt = null;
                if ($lastSeenRaw) {
                    $lastSeenAt = is_numeric($lastSeenRaw)
                        ? Carbon::createFromTimestamp((int) $lastSeenRaw)
                        : Carbon::parse($lastSeenRaw);
                }

                $stale = false;
                if ($lastSeenAt) {
                    $stale = $lastSeenAt->lt(now()->subMinutes(5));
                }

                return [
                    'id' => $executor->id,
                    'display_name' => $executor->display_name ?: $executor->name,
                    'status' => ExecutorStatusPresenter::normalize($executor->status),
                    'last_location' => $lastLocation,
                    'stale_gps' => $stale,
                    'vehicle_type' => $executor->vehicle_type,
                ];
            })
            ->when(! empty($filters['executors_only']), fn ($c) => $c)
            ->values();

        $exceptions = $this->exceptionsQuery->builder($filters, $organizationScope)
            ->whereIn('status', ['open', 'acknowledged', 'investigating', 'mitigated'])
            ->limit(250)
            ->get()
            ->map(fn ($e) => $this->exceptionsQuery->mapRow($e))
            ->values();

        return [
            'jobs' => $jobs,
            'executors' => $executors,
            'exceptions' => $exceptions,
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

