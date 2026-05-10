<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RoadsideSlaBreached extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Order $order
    ) {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $breachType = $this->order->metadata['sla']['breach_type'] ?? 'unknown';
        $breachTime = $this->order->metadata['sla']['breach_time'] ?? 0;

        $breachLabel = match ($breachType) {
            'pending_timeout' => 'Превышено время ожидания назначения',
            'assigned_timeout' => 'Превышено время выполнения',
            default => 'Нарушение SLA',
        };

        return (new MailMessage)
            ->subject("⚠️ Нарушение SLA: Заказ #{$this->order->order_number}")
            ->line("Обнаружено нарушение SLA для roadside-заказа #{$this->order->order_number}")
            ->line("Тип нарушения: {$breachLabel}")
            ->line("Время нарушения: {$breachTime} минут")
            ->action('Открыть заказ', route('filament.resources.orders.view', $this->order))
            ->line('Пожалуйста, проверьте заказ и назначьте исполнителя или партнёра.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $breachType = $this->order->metadata['sla']['breach_type'] ?? 'unknown';
        $breachTime = $this->order->metadata['sla']['breach_time'] ?? 0;

        $breachLabel = match ($breachType) {
            'pending_timeout' => 'Превышено время ожидания назначения',
            'assigned_timeout' => 'Превышено время выполнения',
            default => 'Нарушение SLA',
        };

        return [
            'type' => 'roadside_sla_breached',
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'breach_type' => $breachType,
            'breach_time' => $breachTime,
            'message' => "Нарушение SLA для заказа #{$this->order->order_number}: {$breachLabel} ({$breachTime} мин)",
            'url' => route('filament.resources.orders.view', $this->order),
        ];
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        return $this->toArray($notifiable);
    }
}
