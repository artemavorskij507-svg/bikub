<?php

namespace App\Listeners;

use App\Events\HandymanAssignmentStatusChanged;
use App\Notifications\HandymanAssignmentStatusForCustomer;

class NotifyCustomerAboutHandymanAssignment
{
    public function handle(HandymanAssignmentStatusChanged $event): void
    {
        $assignment = $event->assignment->load('order.user');
        $user = $assignment->order?->user;

        if (! $user) {
            return;
        }

        $user->notify(new HandymanAssignmentStatusForCustomer(
            $assignment,
            $event->oldStatus,
            $event->newStatus
        ));
    }
}
