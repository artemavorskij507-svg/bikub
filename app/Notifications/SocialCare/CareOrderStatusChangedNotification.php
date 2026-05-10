<?php

namespace App\Notifications\SocialCare;

use App\Enums\CareOrderStatus;
use App\Models\CareOrderDetails;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CareOrderStatusChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Order $order,
        public CareOrderDetails $details,
        public CareOrderStatus $oldStatus,
        public CareOrderStatus $newStatus,
        public ?string $reason = null,
    ) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $client = $this->details->clientProfile;
        $statusLabel = match ($this->newStatus) {
            CareOrderStatus::CANCELLED_BY_CLIENT => 'отменён клиентом',
            CareOrderStatus::CANCELLED_BY_OPERATOR => 'отменён оператором',
            CareOrderStatus::CANCELLED_BY_TRUSTED_CONTACT => 'отменён доверенным лицом',
            CareOrderStatus::COMPLETED => 'завершён',
            default => 'изменён',
        };

        $message = (new MailMessage)
            ->subject('Изменение статуса визита Social Care')
            ->greeting('Здравствуйте, '.($notifiable->name ?? ''))
            ->line('Статус визита для '.$client->full_name.' был изменён:')
            ->line('Новый статус: '.$statusLabel)
            ->line('Дата визита: '.$this->details->scheduled_start_at->format('d.m.Y H:i'));

        if ($this->reason) {
            $message->line('Причина: '.$this->reason);
        }

        return $message
            ->action('Просмотреть заказ', route('care.orders.show', $this->order));
    }
}
