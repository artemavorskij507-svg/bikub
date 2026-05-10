<?php

namespace App\Listeners;

use App\Domain\Operations\Events\ServiceJobBroadcasted;
use App\Domain\Operations\Events\ServiceJobStatusChanged;

class BroadcastServiceJobStatusChanged
{
    public function handle(ServiceJobStatusChanged $event): void
    {
        event(new ServiceJobBroadcasted(
            organizationId: $event->job->organization_id,
            jobId: $event->job->id,
            payload: [
                'status' => $event->job->status,
                'from' => $event->from,
                'to' => $event->to,
                'reason' => $event->reason,
                'executor_id' => $event->job->executor_id,
                'assignment_id' => $event->job->assignment_id,
                'updated_at' => optional($event->job->updated_at)->toIso8601String(),
            ],
        ));
    }
}

