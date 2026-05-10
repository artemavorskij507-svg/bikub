<?php

namespace App\Services\Operations;

use App\Models\Operations\Assignment;
use App\Models\Operations\JobTimeline;
use App\Models\Operations\ServiceJob;

class JobTimelineService
{
    public function log(
        ServiceJob $job,
        string $eventType,
        array $payload = [],
        ?Assignment $assignment = null,
        string $actorType = 'system',
        ?int $actorId = null
    ): JobTimeline {
        return JobTimeline::create([
            'organization_id' => $job->organization_id,
            'service_job_id' => $job->id,
            'assignment_id' => $assignment?->id,
            'actor_type' => $actorType,
            'actor_id' => $actorId,
            'event_type' => $eventType,
            'event_payload' => $payload,
            'occurred_at' => now(),
        ]);
    }
}

