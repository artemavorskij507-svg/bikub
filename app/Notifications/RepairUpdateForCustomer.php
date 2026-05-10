<?php

namespace App\Notifications;

use App\Models\RepairUpdate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RepairUpdateForCustomer extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public RepairUpdate $update
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $project = $this->update->project;

        return (new MailMessage)
            ->subject('Новое обновление по вашему ремонту')
            ->greeting('Здравствуйте!')
            ->line('Появилось новое обновление по проекту «'.$project->title.'».')
            ->when($this->update->title, fn (MailMessage $message) => $message->line($this->update->title))
            ->when($this->update->body, fn (MailMessage $message) => $message->line($this->update->body))
            ->action('Открыть проект', route('account.repairs.show', $project))
            ->line('Следите за прогрессом в личном кабинете.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'repair.update',
            'project_id' => $this->update->repair_project_id,
            'update_id' => $this->update->id,
            'progress_percent' => $this->update->progress_percent,
        ];
    }
}
