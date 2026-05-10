<?php

namespace App\Notifications;

use App\Models\RepairProject;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RepairProjectCreatedForManager extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public RepairProject $project
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('Новый проект ремонта назначен')
            ->greeting('Здравствуйте!')
            ->line('Вам назначен проект: '.$this->project->title)
            ->when($this->project->address_line, fn (MailMessage $message) => $message->line('Адрес: '.$this->project->address_line.', '.$this->project->city))
            ->action('Открыть проект', url('/admin/repair-projects/'.$this->project->id.'/edit'))
            ->line('Проверьте этапы и назначьте команду.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'repair.project_assigned',
            'project_id' => $this->project->id,
            'status' => $this->project->status,
        ];
    }
}
<?php

namespace App\Notifications;

use App\Models\RepairProject;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RepairProjectCreatedForManager extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public RepairProject $project
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('Новый проект ремонта назначен')
            ->greeting('Здравствуйте!')
            ->line('Вам назначен проект: '.$this->project->title)
            ->line('Адрес: '.$this->project->address_line.', '.$this->project->city)
            ->action('Открыть в админке', url('/admin/repair-projects/'.$this->project->id.'/edit'))
            ->line('Проверьте этапы и назначьте команду.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'repair.project_assigned',
            'project_id' => $this->project->id,
            'status' => $this->project->status,
        ];
    }
}

