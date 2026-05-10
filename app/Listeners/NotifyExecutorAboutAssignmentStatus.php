<?php

namespace App\Listeners;

use App\Events\HandymanAssignmentStatusChanged;
use App\Notifications\HandymanAssignmentNewForExecutor;
use App\Notifications\HandymanAssignmentUpdatedForExecutor;

class NotifyExecutorAboutAssignmentStatus
{
    public function handle(HandymanAssignmentStatusChanged $event): void
    {
        $assignment = $event->assignment->load('executorProfile.user', 'order');
        $executorUser = $assignment->executorProfile?->user;

        if (! $executorUser) {
            return;
        }

        if ($event->oldStatus === 'new' && $event->newStatus === 'proposed') {
            $executorUser->notify(new HandymanAssignmentNewForExecutor($assignment));

            return;
        }

        $executorUser->notify(new HandymanAssignmentUpdatedForExecutor(
            $assignment,
            $event->oldStatus,
            $event->newStatus
        ));
    }
}
