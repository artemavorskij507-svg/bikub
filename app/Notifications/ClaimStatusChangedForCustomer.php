<?php

namespace App\Notifications;

use App\Models\Claim;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ClaimStatusChangedForCustomer extends Notification implements ShouldQueue
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
            ->subject('Статус вашей претензии обновлён')
            ->greeting('Здравствуйте!')
            ->line('Претензия #'.$this->claim->id.' теперь имеет статус: '.$this->claim->status)
            ->when($this->claim->resolution_notes, fn (MailMessage $message) => $message->line($this->claim->resolution_notes))
            ->action('Открыть заказ', route('account.orders.show', $this->claim->order_id))
            ->line('Спасибо, что помогаете улучшать сервис.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'claim.status_changed',
            'claim_id' => $this->claim->id,
            'order_id' => $this->claim->order_id,
            'status' => $this->claim->status,
        ];
    }
}
