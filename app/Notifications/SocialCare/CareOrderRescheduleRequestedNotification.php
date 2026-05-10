<?php

namespace App\Notifications\SocialCare;

use App\Models\CareOrderChangeRequest;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CareOrderRescheduleRequestedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Order $order,
        public CareOrderChangeRequest $changeRequest,
    ) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $details = $this->order->careDetails;
        $client = $details?->clientProfile;

        return (new MailMessage)
            ->subject('Запрос на перенос визита Social Care')
            ->greeting('Здравствуйте, '.($notifiable->name ?? ''))
            ->line('Поступил запрос на перенос визита:')
            ->line('Клиент: '.($client->full_name ?? '—'))
            ->line('Текущая дата: '.($details->scheduled_start_at?->format('d.m.Y H:i') ?? '—'))
            ->line('Запрошенная новая дата: '.$this->changeRequest->requested_new_start_at->format('d.m.Y H:i'))
            ->when($this->changeRequest->reason, function ($mail) {
                return $mail->line('Причина: '.$this->changeRequest->reason);
            })
            ->action('Обработать запрос', url('/admin/social-care-orders/'.$this->order->id))
            ->line('Пожалуйста, обработайте запрос в ближайшее время.');
    }
}
