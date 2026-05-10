<?php

namespace App\Listeners;

use App\Events\ClaimOpened;
use App\Models\User;
use App\Notifications\ClaimOpenedForOperator;
use Illuminate\Support\Facades\Notification;

class NotifyOperatorAboutClaim
{
    public function handle(ClaimOpened $event): void
    {
        $admins = User::role('admin')->get();

        if ($admins->isEmpty()) {
            return;
        }

        Notification::send($admins, new ClaimOpenedForOperator($event->claim));
    }
}
