<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class HandymanOrderCreatedForCustomer extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Order $order
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Мы получили ваш заказ мастера')
            ->greeting('Здравствуйте!')
            ->line('Мы приняли ваш заказ на услугу «Мастер на час».')
            ->line('Номер заказа: '.($this->order->order_number ?? $this->order->id))
            ->action('Посмотреть заказ', route('account.orders.show', $this->order))
            ->line('Мы сообщим, когда мастер будет назначен.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'handyman.order_created',
            'order_id' => $this->order->id,
            'service_type' => $this->order->service_type,
        ];
    }
}
