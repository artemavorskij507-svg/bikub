<?php

namespace App\Listeners;

use App\Events\RepairProjectCreated;
use App\Notifications\RepairProjectCreatedForManager;

class NotifyProjectManagerAboutRepairProject
{
    public function handle(RepairProjectCreated $event): void
    {
        $project = $event->project->load('projectManager.user');
        $managerUser = $project->projectManager?->user;

        if (! $managerUser) {
            return;
        }

        $managerUser->notify(new RepairProjectCreatedForManager($project));
    }
}
