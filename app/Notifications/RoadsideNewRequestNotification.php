<?php

namespace App\Notifications;

use App\Models\RoadsideEmergency;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\DatabaseMessage;

class RoadsideNewRequestNotification extends Notification
{
    use Queueable;

    public function __construct(
        public RoadsideEmergency $emergency,
        public Order $order
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        $typeLabel = match($this->emergency->incident_type) {
            'tow_needed' => 'Эвакуатор',
            'jump_start' => 'Прикуривание',
            'fuel' => 'Топливо',
            'flat_tire' => 'Прокол',
            default => 'Помощь на дороге',
        };

        return [
            'type' => 'roadside_new_request',
            'title' => "Новый запрос: {$typeLabel}",
            'message' => "Клиент: {$this->emergency->customer->name ?? 'N/A'}, Телефон: " . ($this->emergency->metadata['phone'] ?? 'N/A'),
            'emergency_id' => $this->emergency->id,
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'incident_type' => $this->emergency->incident_type,
            'location' => $this->emergency->metadata['location_text'] ?? null,
            'url' => \App\Filament\Resources\RoadsideEmergencyResource::getUrl('edit', ['record' => $this->emergency]),
        ];
    }
}

