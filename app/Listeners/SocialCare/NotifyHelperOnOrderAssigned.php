<?php

namespace App\Listeners\SocialCare;

use App\Events\SocialCare\CareOrderAssignedToHelper;
use App\Notifications\SocialCare\OrderAssignedToHelperNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class NotifyHelperOnOrderAssigned implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(CareOrderAssignedToHelper $event): void
    {
        $order = $event->order;
        $details = $event->details;
        $helper = $event->helperProfile;
        $user = $helper->user;

        if (! $user || ! $user->email) {
            return;
        }

        $user->notify(new OrderAssignedToHelperNotification($order, $details, $helper));
    }
}
