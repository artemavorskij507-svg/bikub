<?php

namespace App\Listeners;

use App\Domain\Operations\Actions\WriteJobTimelineAction;
use App\Domain\Operations\Events\ServiceJobStatusChanged;

class WriteTimelineWhenServiceJobStatusChanged
{
    public function __construct(
        private readonly WriteJobTimelineAction $writeJobTimelineAction,
    ) {}

    public function handle(ServiceJobStatusChanged $event): void
    {
        $this->writeJobTimelineAction->execute(
            job: $event->job,
            eventType: 'service_job_status_transition',
            actorType: data_get($event->context, 'actor_type', 'system'),
            actorId: data_get($event->context, 'actor_id'),
            assignmentId: $event->job->assignment_id,
            payload: [
                'from' => $event->from,
                'to' => $event->to,
                'reason' => $event->reason,
                'context' => $event->context,
            ],
        );
    }
}

