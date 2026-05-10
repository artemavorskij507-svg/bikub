<?php

namespace App\Domain\Ops\Queries;

use App\Domain\Routing\Models\ReplanRecommendation;
use App\Models\Operations\Executor;
use App\Models\Operations\ServiceJob;

class ReplanRecommendationsQuery
{
    public function execute(string $organizationId, ?int $serviceJobId = null, int $limit = 50): array
    {
        $query = ReplanRecommendation::query()
            ->where('organization_id', (string) $organizationId)
            ->whereIn('status', ['open', 'acknowledged'])
            ->orderByDesc('detected_at')
            ->limit(max(1, min(200, $limit)));

        if ($serviceJobId !== null) {
            $query->where('service_job_id', $serviceJobId);
        }

        $rows = $query->get();
        $jobIds = $rows->pluck('service_job_id')->filter()->unique()->all();
        $executorIds = $rows->pluck('current_executor_id')
            ->merge($rows->pluck('recommended_executor_id'))
            ->filter()
            ->unique()
            ->all();

        $jobs = ServiceJob::query()
            ->whereIn('id', $jobIds)
            ->get(['id', 'service_domain', 'job_kind', 'status', 'priority'])
            ->keyBy('id');

        $executors = Executor::query()
            ->whereIn('id', $executorIds)
            ->get(['id', 'display_name', 'name', 'status'])
            ->keyBy('id');

        return $rows->map(function (ReplanRecommendation $row) use ($jobs, $executors): array {
            $job = $jobs->get($row->service_job_id);
            $current = $row->current_executor_id ? $executors->get($row->current_executor_id) : null;
            $recommended = $row->recommended_executor_id ? $executors->get($row->recommended_executor_id) : null;

            return [
                'id' => $row->id,
                'type' => $row->type,
                'severity' => $row->severity,
                'status' => $row->status,
                'detected_at' => optional($row->detected_at)->toIso8601String(),
                'service_job_id' => $row->service_job_id,
                'job' => $job ? [
                    'id' => $job->id,
                    'service_domain' => $job->service_domain,
                    'job_kind' => $job->job_kind,
                    'status' => $job->status,
                    'priority' => $job->priority,
                ] : null,
                'current_executor' => $current ? [
                    'id' => $current->id,
                    'display_name' => $current->display_name ?: $current->name,
                    'status' => $current->status,
                ] : null,
                'recommended_executor' => $recommended ? [
                    'id' => $recommended->id,
                    'display_name' => $recommended->display_name ?: $recommended->name,
                    'status' => $recommended->status,
                ] : null,
                'payload' => (array) $row->payload,
            ];
        })->values()->all();
    }
}

