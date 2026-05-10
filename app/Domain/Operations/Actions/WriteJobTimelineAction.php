<?php

namespace App\Domain\Operations\Actions;

use App\Domain\Operations\Models\JobTimeline;
use App\Models\Operations\ServiceJob;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

class WriteJobTimelineAction
{
    private static ?bool $hasTenantColumn = null;

    public function execute(
        ServiceJob $job,
        string $eventType,
        string $actorType = 'system',
        ?int $actorId = null,
        ?int $assignmentId = null,
        array $payload = [],
        Carbon|string|null $occurredAt = null,
    ): JobTimeline {
        $attributes = [
            'organization_id' => $job->organization_id,
            'service_job_id' => $job->id,
            'assignment_id' => $assignmentId,
            'actor_type' => $actorType,
            'actor_id' => $actorId,
            'event_type' => $eventType,
            'event_payload' => $payload,
            'occurred_at' => $occurredAt ? Carbon::parse($occurredAt) : now(),
        ];

        if ($this->jobTimelineHasTenantColumn()) {
            $attributes['tenant_id'] = $job->tenant_id;
        }

        return JobTimeline::query()->create($attributes);
    }

    private function jobTimelineHasTenantColumn(): bool
    {
        if (self::$hasTenantColumn === null) {
            self::$hasTenantColumn = Schema::hasColumn('job_timelines', 'tenant_id');
        }

        return self::$hasTenantColumn;
    }
}
