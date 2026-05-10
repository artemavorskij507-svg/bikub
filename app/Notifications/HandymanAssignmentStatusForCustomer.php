<?php

namespace App\Notifications;

use App\Models\HandymanAssignment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class HandymanAssignmentStatusForCustomer extends Notification implements ShouldQueue
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
            ->subject('Статус задания мастера обновлён')
            ->greeting('Здравствуйте!')
            ->line('Статус вашего задания изменился: '.$this->formatStatus($this->newStatus))
            ->action('Посмотреть заказ', route('account.orders.show', $this->assignment->order))
            ->line('Спасибо, что пользуетесь GLF Bikube.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'handyman.assignment_status',
            'order_id' => $this->assignment->order_id,
            'assignment_id' => $this->assignment->id,
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
        ];
    }

    protected function formatStatus(string $status): string
    {
        return match ($status) {
            'proposed' => 'Предложено',
            'accepted' => 'Мастер принял задачу',
            'declined' => 'Мастер отказался',
            'in_route' => 'Мастер в пути',
            'started' => 'Мастер начал работу',
            'finished', 'completed' => 'Работа завершена',
            default => ucfirst($status),
        };
    }
}
