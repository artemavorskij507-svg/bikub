<?php

namespace App\Listeners\SocialCare;

use App\Events\SocialCare\SocialCareEmergencyTriggered;
use App\Notifications\SocialCare\SocialCareEmergencyNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Notification;

class NotifyCoordinatorsOnSocialCareEmergency implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(SocialCareEmergencyTriggered $event): void
    {
        $emergency = $event->emergency;

        // Найти координаторов и админов
        $recipients = \App\Models\User::query()
            ->get()
            ->filter(function ($user) use ($emergency) {
                $hasRole = $user->hasRole('social-coordinator') ||
                          ($emergency->level === 'CRITICAL' && $user->hasRole('admin'));

                return $hasRole && $user->wantsSocialCareNotification('notify_emergency');
            });

        Notification::send($recipients, new SocialCareEmergencyNotification($emergency));

        // Опционально: уведомить доверенное лицо при CRITICAL
        if ($emergency->level === 'CRITICAL' && $emergency->clientProfile) {
            $trusted = $emergency->clientProfile->trustedContacts()
                ->where('is_primary', true)
                ->first();

            if ($trusted && $trusted->user && $trusted->user->email &&
                $trusted->user->wantsSocialCareNotification('notify_emergency')) {
                $trusted->user->notify(new SocialCareEmergencyNotification($emergency));
            }
        }
    }
}
