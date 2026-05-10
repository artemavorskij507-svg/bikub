<?php

namespace App\Notifications;

use App\Models\Claim;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ClaimOpenedForOperator extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Claim $claim
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Новая претензия клиента')
            ->greeting('Здравствуйте!')
            ->line('Получена новая претензия #'.$this->claim->id.' по заказу #'.$this->claim->order_id.'.')
            ->line('Статус: '.$this->claim->status)
            ->action('Открыть претензию', url('/admin/claims/'.$this->claim->id.'/edit'))
            ->line('Пожалуйста, рассмотрите её.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'claim.opened',
            'claim_id' => $this->claim->id,
            'order_id' => $this->claim->order_id,
            'status' => $this->claim->status,
        ];
    }
}
