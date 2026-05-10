<?php

namespace App\Listeners;

use App\Domain\Operations\Events\ServiceJobStatusChanged;
use App\Events\Operations\JobStatusChanged;

class BroadcastJobStatusBridge
{
    public function handle(ServiceJobStatusChanged $event): void
    {
        event(new JobStatusChanged(
            job: $event->job,
            fromStatus: $event->from,
            toStatus: $event->to,
        ));
    }
}

