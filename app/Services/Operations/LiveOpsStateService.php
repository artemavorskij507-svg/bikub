<?php

namespace App\Services\Operations;

use App\Models\Operations\Executor;
use App\Models\Operations\OperationException;
use App\Models\Operations\ServiceJob;
use App\Support\Ops\ExecutorStatusPresenter;
use App\Support\Ops\JobStatusPresenter;
use Illuminate\Support\Facades\Redis;

class LiveOpsStateService
{
    public function getState(array $filters = []): array
    {
        $jobsQuery = ServiceJob::query()->with(['activeAssignment.executor', 'slaTimer'])
            ->whereIn('service_domain', ['delivery', 'handyman'])
            ->orderByDesc('id');

        if (! empty($filters['organization_id'])) {
            $jobsQuery->where('organization_id', $filters['organization_id']);
        }
        if (! empty($filters['service_domain'])) {
            $jobsQuery->where('service_domain', $filters['service_domain']);
        }
        if (! empty($filters['status'])) {
            $jobsQuery->where('status', $filters['status']);
        }

        $jobs = $jobsQuery->limit(150)->get();

        $executors = Executor::query()
            ->when(! empty($filters['organization_id']), fn ($q) => $q->where('organization_id', $filters['organization_id']))
            ->whereIn('status', ['offline', 'available', 'busy', 'paused', 'suspended', 'online', 'idle'])
            ->limit(200)
            ->get()
            ->map(function (Executor $executor) {
                $cached = $this->readLiveLocationFromRedis($executor->id);

                return [
                    'id' => $executor->id,
                    'name' => $executor->display_name ?: $executor->name,
                    'status' => ExecutorStatusPresenter::normalize($executor->status),
                    'type' => $executor->executor_type,
                    'last_seen_at' => $executor->last_seen_at,
                    'live_location' => $cached,
                ];
            });

        $exceptions = OperationException::query()
            ->when(! empty($filters['organization_id']), fn ($q) => $q->where('organization_id', $filters['organization_id']))
            ->where('status', 'open')
            ->orderByDesc('detected_at')
            ->limit(100)
            ->get();

        return [
            'jobs' => $jobs,
            'executors' => $executors,
            'exceptions' => $exceptions,
            'summary' => [
                'total_jobs' => $jobs->count(),
                'pending_jobs' => $jobs->filter(fn ($j) => JobStatusPresenter::normalize($j->status) === 'pending_dispatch')->count(),
                'assigned_jobs' => $jobs->filter(fn ($j) => JobStatusPresenter::normalize($j->status) === 'assigned')->count(),
                'started_jobs' => $jobs->filter(fn ($j) => JobStatusPresenter::normalize($j->status) === 'in_progress')->count(),
                'sla_warning_jobs' => $jobs->filter(fn ($j) => in_array($j->slaTimer?->completion_state, ['warning', 'breached'], true))->count(),
                'open_exceptions' => $exceptions->count(),
            ],
        ];
    }

    public function updateExecutorHotLocation(int $executorId, float $lat, float $lng, array $extra = []): void
    {
        $payload = array_merge($extra, [
            'lat' => $lat,
            'lng' => $lng,
            'updated_at' => now()->toIso8601String(),
        ]);

        // Canonical keys
        Redis::setex("executor:{$executorId}:last_location", 300, json_encode($payload));
        Redis::setex("executor:{$executorId}:last_seen_at", 300, (string) now()->timestamp);
        if (isset($extra['status'])) {
            Redis::setex("executor:{$executorId}:status", 300, (string) $extra['status']);
        }
        if (array_key_exists('active_assignment_id', $extra)) {
            Redis::setex("executor:{$executorId}:active_assignment_id", 300, (string) ($extra['active_assignment_id'] ?? ''));
        }

        if (isset($extra['service_job_id'])) {
            Redis::setex("job:{$extra['service_job_id']}:live_status", 300, (string) ($extra['job_status'] ?? 'in_progress'));
            if (isset($extra['live_eta'])) {
                Redis::setex("job:{$extra['service_job_id']}:live_eta", 300, (string) $extra['live_eta']);
            }
        }

        // Legacy key
        Redis::setex("operations:executor:{$executorId}:live", 300, json_encode($payload));
    }

    private function readLiveLocationFromRedis(int $executorId): ?array
    {
        $raw = Redis::get("executor:{$executorId}:last_location");
        if (! $raw) {
            $raw = Redis::get("operations:executor:{$executorId}:live");
        }
        if (! $raw) {
            return null;
        }

        return json_decode($raw, true);
    }
}
