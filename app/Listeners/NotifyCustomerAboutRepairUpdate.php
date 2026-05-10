<?php

namespace App\Listeners;

use App\Events\RepairUpdateCreated;
use App\Notifications\RepairUpdateForCustomer;

class NotifyCustomerAboutRepairUpdate
{
    public function handle(RepairUpdateCreated $event): void
    {
        $update = $event->update->load('project.order.user');
        $user = $update->project->order?->user;

        if (! $user) {
            return;
        }

        $user->notify(new RepairUpdateForCustomer($update));
    }
}
