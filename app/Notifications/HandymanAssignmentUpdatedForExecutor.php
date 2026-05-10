<?php

namespace App\Notifications;

use App\Models\HandymanAssignment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class HandymanAssignmentUpdatedForExecutor extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public HandymanAssignment $assignment,
        public string $oldStatus,
        public string $newStatus
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Статус задачи обновлён')
            ->greeting('Здравствуйте!')
            ->line('Статус задания по заказу №'.($this->assignment->order->order_number ?? $this->assignment->order_id).' изменился.')
            ->line('Новый статус: '.$this->newStatus)
            ->action('Перейти в кабинет', route('executor.jobs.show', $this->assignment))
            ->line('Не забудьте обновлять статус вовремя.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'handyman.assignment_updated',
            'assignment_id' => $this->assignment->id,
            'order_id' => $this->assignment->order_id,
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
        ];
    }
}
