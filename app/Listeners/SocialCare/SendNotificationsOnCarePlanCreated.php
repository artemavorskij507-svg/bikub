<?php

namespace App\Listeners\SocialCare;

use App\Events\SocialCare\CarePlanCreated;
use App\Notifications\SocialCare\CarePlanCreatedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendNotificationsOnCarePlanCreated implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(CarePlanCreated $event): void
    {
        try {
            $carePlan = $event->carePlan;
            $client = $carePlan->clientProfile;
            $trusted = $carePlan->trustedContact;

            // Уведомление клиента
            if ($client->user && $client->user->email &&
                $client->user->wantsSocialCareNotification('notify_care_plan_created')) {
                $client->user->notify(
                    new CarePlanCreatedNotification($carePlan)
                );
            }

            // Уведомление доверенного лица
            if ($trusted && $trusted->user && $trusted->user->email &&
                $trusted->user->wantsSocialCareNotification('notify_care_plan_created')) {
                $trusted->user->notify(
                    new CarePlanCreatedNotification($carePlan)
                );
            }
        } catch (\Exception $e) {
            // Логируем ошибку, но не прерываем выполнение
            \Illuminate\Support\Facades\Log::warning('Failed to send care plan notification', [
                'care_plan_id' => $event->carePlan->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
