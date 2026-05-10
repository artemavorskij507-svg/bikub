<?php

namespace App\Listeners;

use App\Events\RepairProjectCreated;
use App\Notifications\RepairProjectCreatedForCustomer;

class NotifyCustomerAboutRepairProject
{
    public function handle(RepairProjectCreated $event): void
    {
        $project = $event->project->load('order.user');
        $user = $project->order?->user;

        if (! $user) {
            return;
        }

        $user->notify(new RepairProjectCreatedForCustomer($project));
    }
}
