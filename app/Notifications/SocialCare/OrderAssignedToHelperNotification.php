<?php

namespace App\Notifications\SocialCare;

use App\Models\CareOrderDetails;
use App\Models\Order;
use App\Models\SocialHelperProfile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderAssignedToHelperNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Order $order,
        public CareOrderDetails $details,
        public SocialHelperProfile $helperProfile,
    ) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $client = $this->details->clientProfile;
        $service = $this->details->careService;

        return (new MailMessage)
            ->subject('Вам назначен визит Social Care')
            ->greeting('Здравствуйте, '.($notifiable->name ?? $this->helperProfile->display_name))
            ->line('Вам назначен новый визит:')
            ->line('Клиент: '.$client->full_name)
            ->line('Дата и время: '.$this->details->scheduled_start_at->format('d.m.Y H:i'))
            ->line('Услуга: '.($service->name ?? '—'))
            ->when($this->details->notes_for_helper, function ($mail) {
                return $mail->line('Примечания: '.$this->details->notes_for_helper);
            })
            ->action('Открыть расписание', url('/api/v1/helper/visits/upcoming'))
            ->line('Спасибо за вашу работу!');
    }
}
