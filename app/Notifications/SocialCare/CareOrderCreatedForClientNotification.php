<?php

namespace App\Notifications\SocialCare;

use App\Models\CareOrderDetails;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CareOrderCreatedForClientNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Order $order,
        public CareOrderDetails $details,
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
            ->subject('Создан заказ Social Care')
            ->greeting('Здравствуйте, '.($notifiable->name ?? $client->full_name))
            ->line('Для вас создан заказ на социальный визит:')
            ->line('Услуга: '.($service->name ?? '—'))
            ->line('Дата и время: '.$this->details->scheduled_start_at->format('d.m.Y H:i'))
            ->when($this->details->notes_for_helper, function ($mail) {
                return $mail->line('Примечания: '.$this->details->notes_for_helper);
            })
            ->action('Просмотреть заказ', route('care.orders.show', $this->order))
            ->line('Спасибо за использование наших услуг!');
    }
}
