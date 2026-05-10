<?php

namespace App\Notifications\SocialCare;

use App\Models\CareOrderDetails;
use App\Models\Order;
use App\Models\VisitReport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VisitReportForTrustedContactNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Order $order,
        public CareOrderDetails $details,
        public VisitReport $report,
    ) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $client = $this->details->clientProfile;
        $helper = $this->report->helperProfile;

        $message = (new MailMessage)
            ->subject('Отчёт о визите Social Care для '.$client->full_name)
            ->greeting('Здравствуйте, '.($notifiable->name ?? ''))
            ->line('Визит для вашего подопечного '.$client->full_name.' завершён.')
            ->line('Помощник: '.($helper->display_name ?? $helper->user->name ?? '—'))
            ->line('Дата визита: '.$this->details->scheduled_start_at->format('d.m.Y H:i'));

        if ($this->report->summary) {
            $message->line('Краткое описание: '.$this->report->summary);
        }

        if ($this->report->client_mood) {
            $moodLabel = match ($this->report->client_mood) {
                'HAPPY' => 'Хорошее',
                'NEUTRAL' => 'Нейтральное',
                'CONCERNED' => 'Вызывает беспокойство',
                default => $this->report->client_mood,
            };
            $message->line('Настроение клиента: '.$moodLabel);
        }

        if ($this->report->issues_noted) {
            $message->line('Отмеченные проблемы: '.$this->report->issues_noted);
        }

        if ($this->report->followup_recommended) {
            $message->line('Рекомендуется последующий визит.');
            if ($this->report->followup_notes) {
                $message->line('Примечания: '.$this->report->followup_notes);
            }
        }

        return $message
            ->action('Просмотреть отчёт', route('care.orders.show', $this->order));
    }
}
