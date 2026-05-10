<?php

namespace App\Domain\Dispatch\Actions;

use App\Domain\Dispatch\Exceptions\ExecutorUnavailableException;
use App\Models\Operations\Assignment;
use App\Models\Operations\Executor;
use App\Models\Operations\ServiceJob;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Redis;

class EnsureExecutorAssignableAction
{
    public function execute(ServiceJob $job, Executor $executor): void
    {
        if ((string) $executor->organization_id !== (string) $job->organization_id) {
            throw new ExecutorUnavailableException('executor_wrong_organization');
        }

        if (in_array((string) $executor->status, ['suspended', 'offline'], true)) {
            throw new ExecutorUnavailableException('executor_unavailable_status');
        }

        $lastSeenRaw = Redis::get("executor:{$executor->id}:last_seen_at");
        if ($lastSeenRaw) {
            $lastSeen = is_numeric($lastSeenRaw)
                ? Carbon::createFromTimestamp((int) $lastSeenRaw)
                : Carbon::parse((string) $lastSeenRaw);
            if ($lastSeen->lt(now()->subMinutes(10))) {
                throw new ExecutorUnavailableException('executor_stale_gps');
            }
        }

        $maxConcurrent = (int) ($executor->max_concurrent_jobs ?: 1);
        $activeCount = Assignment::query()
            ->where('executor_id', $executor->id)
            ->whereIn('status', ['proposed', 'offered', 'accepted', 'active'])
            ->count();
        if ($activeCount >= $maxConcurrent) {
            throw new ExecutorUnavailableException('executor_capacity_exceeded');
        }
    }
}

