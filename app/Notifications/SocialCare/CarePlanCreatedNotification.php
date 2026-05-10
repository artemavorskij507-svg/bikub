<?php

namespace App\Notifications\SocialCare;

use App\Models\CarePlan;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CarePlanCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public CarePlan $carePlan,
    ) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $client = $this->carePlan->clientProfile;
        $service = $this->carePlan->careService;

        return (new MailMessage)
            ->subject('Создан план ухода Social Care')
            ->greeting('Здравствуйте, '.($notifiable->name ?? $client->full_name))
            ->line('Для '.$client->full_name.' создан план регулярного ухода:')
            ->line('Услуга: '.($service->name ?? '—'))
            ->line('Частота: '.$this->carePlan->frequency)
            ->when($this->carePlan->time_of_day, function ($mail) {
                return $mail->line('Время: '.$this->carePlan->time_of_day);
            })
            ->when($this->carePlan->starts_at, function ($mail) {
                return $mail->line('Начало: '.$this->carePlan->starts_at->format('d.m.Y'));
            })
            ->action('Просмотреть план', url('/care/dashboard'))
            ->line('План будет автоматически создавать заказы согласно расписанию.');
    }
}
