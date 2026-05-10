<?php

namespace App\Notifications;

use App\Models\RoadsideEmergency;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class RoadsideEmergencyStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public RoadsideEmergency $emergency,
        public string $oldStatus
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
        $statusLabels = [
            'new' => 'Новый',
            'assigned' => 'Назначен',
            'on_route' => 'В пути',
            'on_spot' => 'На месте',
            'in_progress' => 'В работе',
            'completed' => 'Завершен',
            'cancelled' => 'Отменен',
            'rejected' => 'Отклонен',
            'failed' => 'Не выполнен',
        ];

        $typeLabel = match($this->emergency->incident_type) {
            'tow_needed' => 'Эвакуатор',
            'jump_start' => 'Прикуривание',
            'fuel' => 'Топливо',
            'flat_tire' => 'Прокол',
            default => 'Помощь на дороге',
        };

        return [
            'type' => 'roadside.job_status_changed',
            'title' => "Статус заявки #{$this->emergency->id} изменён",
            'message' => "{$typeLabel}: {$statusLabels[$this->oldStatus] ?? $this->oldStatus} → {$statusLabels[$this->emergency->status] ?? $this->emergency->status}",
            'job_id' => $this->emergency->id,
            'order_id' => $this->emergency->order_id,
            'old_status' => $this->oldStatus,
            'new_status' => $this->emergency->status,
            'changed_at' => now()->toIso8601String(),
            'url' => $this->emergency->order_id 
                ? \App\Filament\Resources\OrderResource::getUrl('edit', ['record' => $this->emergency->order_id])
                : null,
        ];
    }
}

