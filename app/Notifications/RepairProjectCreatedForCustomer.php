<?php

namespace App\Notifications;

use App\Models\RepairProject;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RepairProjectCreatedForCustomer extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public RepairProject $project
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Мы стартовали проект вашего ремонта')
            ->greeting('Здравствуйте!')
            ->line('Мы создали проект «'.$this->project->title.'».')
            ->line('Статус: '.$this->project->status)
            ->action('Открыть проект', route('account.repairs.show', $this->project))
            ->line('Все обновления будут появляться в разделе «Мой ремонт».');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'repair.project_created',
            'project_id' => $this->project->id,
            'order_id' => $this->project->order_id,
            'status' => $this->project->status,
        ];
    }
}
