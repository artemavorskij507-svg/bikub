<?php

namespace App\Listeners\SocialCare;

use App\Events\SocialCare\CareOrderCreated;
use App\Notifications\SocialCare\CareOrderCreatedForClientNotification;
use App\Notifications\SocialCare\CareOrderCreatedForTrustedContactNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendNotificationsOnCareOrderCreated implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(CareOrderCreated $event): void
    {
        $order = $event->order;
        $details = $event->details;

        $client = $details->clientProfile;
        $trusted = $details->trustedContact;

        // Уведомление клиента
        if ($client->user && $client->user->email &&
            $client->user->wantsSocialCareNotification('notify_care_order_created')) {
            $client->user->notify(
                new CareOrderCreatedForClientNotification($order, $details)
            );
        }

        // Уведомление доверенного лица
        if ($trusted && $trusted->user && $trusted->user->email &&
            $trusted->user->wantsSocialCareNotification('notify_care_order_created')) {
            $trusted->user->notify(
                new CareOrderCreatedForTrustedContactNotification($order, $details)
            );
        }
    }
}
