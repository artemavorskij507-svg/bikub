<?php

namespace App\Notifications\SocialCare;

use App\Models\SocialCareEmergencyEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SocialCareEmergencyNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public SocialCareEmergencyEvent $emergency,
    ) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $levelLabel = match ($this->emergency->level) {
            'INFO' => 'Информация',
            'WARNING' => 'Предупреждение',
            'CRITICAL' => 'КРИТИЧЕСКОЕ',
            default => $this->emergency->level,
        };

        $message = (new MailMessage)
            ->subject('['.$levelLabel.'] Экстренный сигнал Social Care')
            ->greeting('ВНИМАНИЕ!')
            ->line('Поступил экстренный сигнал от помощника:')
            ->line('Уровень: '.$levelLabel)
            ->line('Источник: '.match ($this->emergency->source) {
                'HELPER_APP' => 'Приложение помощника',
                'CLIENT_APP' => 'Приложение клиента',
                'COORDINATOR' => 'Координатор',
                default => $this->emergency->source,
            });

        if ($this->emergency->helperProfile) {
            $message->line('Помощник: '.($this->emergency->helperProfile->display_name ?? '—'));
        }

        if ($this->emergency->clientProfile) {
            $message->line('Клиент: '.$this->emergency->clientProfile->full_name);
        }

        if ($this->emergency->order) {
            $message->line('Заказ: #'.$this->emergency->order->order_number);
        }

        if ($this->emergency->message) {
            $message->line('Сообщение: '.$this->emergency->message);
        }

        return $message
            ->action('Открыть в панели координатора', url('/admin/social-care-dashboard'))
            ->error();
    }
}
