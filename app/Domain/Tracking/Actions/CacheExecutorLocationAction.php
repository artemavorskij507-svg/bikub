<?php

namespace App\Domain\Tracking\Actions;

use App\Domain\Tracking\Events\ExecutorLocationUpdated;
use App\Domain\Tracking\Models\ExecutorLocation;
use Illuminate\Support\Facades\Redis;

class CacheExecutorLocationAction
{
    public function execute(ExecutorLocation $location): void
    {
        Redis::setex("executor:{$location->executor_id}:last_location", 300, json_encode([
            'latitude' => $location->latitude ?: $location->lat,
            'longitude' => $location->longitude ?: $location->lng,
            'heading' => $location->heading,
            'speed' => $location->speed ?: $location->speed_kmh,
            'accuracy' => $location->accuracy,
            'recorded_at' => optional($location->recorded_at)->toIso8601String(),
            'assignment_id' => $location->assignment_id,
            'service_job_id' => $location->service_job_id,
        ]));

        Redis::setex("executor:{$location->executor_id}:last_seen_at", 300, now()->toIso8601String());

        event(new ExecutorLocationUpdated(
            organizationId: $location->organization_id,
            executorId: $location->executor_id,
            payload: [
                'latitude' => $location->latitude ?: $location->lat,
                'longitude' => $location->longitude ?: $location->lng,
                'heading' => $location->heading,
                'speed' => $location->speed ?: $location->speed_kmh,
                'accuracy' => $location->accuracy,
                'recorded_at' => optional($location->recorded_at)->toIso8601String(),
                'assignment_id' => $location->assignment_id,
                'service_job_id' => $location->service_job_id,
            ],
        ));
    }
}

