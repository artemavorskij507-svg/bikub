<?php

namespace App\Listeners\SocialCare;

use App\Events\SocialCare\CareOrderStatusChanged;
use App\Notifications\SocialCare\CareOrderStatusChangedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendNotificationsOnCareOrderStatusChanged implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(CareOrderStatusChanged $event): void
    {
        $order = $event->order;
        $details = $event->details;
        $client = $details->clientProfile;
        $trusted = $details->trustedContact;
        $helper = $details->assignedHelper;

        // Уведомление клиента
        if ($client->user && $client->user->email &&
            $client->user->wantsSocialCareNotification('notify_visit_status_changes')) {
            $client->user->notify(
                new CareOrderStatusChangedNotification(
                    $order,
                    $details,
                    $event->oldStatus,
                    $event->newStatus,
                    $event->reason
                )
            );
        }

        // Уведомление доверенного лица
        if ($trusted && $trusted->user && $trusted->user->email &&
            $trusted->user->wantsSocialCareNotification('notify_visit_status_changes')) {
            $trusted->user->notify(
                new CareOrderStatusChangedNotification(
                    $order,
                    $details,
                    $event->oldStatus,
                    $event->newStatus,
                    $event->reason
                )
            );
        }

        // Уведомление помощника (если назначен)
        if ($helper && $helper->user && $helper->user->email) {
            $helper->user->notify(
                new CareOrderStatusChangedNotification(
                    $order,
                    $details,
                    $event->oldStatus,
                    $event->newStatus,
                    $event->reason
                )
            );
        }
    }
}
