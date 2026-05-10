<?php

namespace App\Notifications;

use App\Models\HandymanAssignment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class HandymanAssignmentNewForExecutor extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public HandymanAssignment $assignment
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Новое предложение задачи')
            ->greeting('Здравствуйте!')
            ->line('Вам предложено новое задание по заказу №'.($this->assignment->order->order_number ?? $this->assignment->order_id).'.')
            ->line('Статус: '.$this->assignment->status)
            ->action('Открыть задачу', route('executor.jobs.show', $this->assignment))
            ->line('Пожалуйста, подтвердите или отклоните задачу.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'handyman.assignment_new',
            'assignment_id' => $this->assignment->id,
            'order_id' => $this->assignment->order_id,
            'status' => $this->assignment->status,
        ];
    }
}
