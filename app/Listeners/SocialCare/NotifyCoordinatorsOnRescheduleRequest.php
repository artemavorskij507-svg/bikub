<?php

namespace App\Listeners\SocialCare;

use App\Events\SocialCare\CareOrderRescheduleRequested;
use App\Notifications\SocialCare\CareOrderRescheduleRequestedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Notification;

class NotifyCoordinatorsOnRescheduleRequest implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(CareOrderRescheduleRequested $event): void
    {
        $order = $event->order;
        $changeRequest = $event->changeRequest;

        // Найти координаторов
        $coordinators = \App\Models\User::query()
            ->get()
            ->filter(function ($user) {
                return ($user->hasAnyRole(['social-coordinator', 'admin', 'operator']) ||
                        $user->hasRole('social-coordinator') ||
                        $user->hasRole('admin') ||
                        $user->hasRole('operator'))
                    && $user->wantsSocialCareNotification('notify_reschedule_requests');
            });

        Notification::send(
            $coordinators,
            new CareOrderRescheduleRequestedNotification($order, $changeRequest)
        );
    }
}
