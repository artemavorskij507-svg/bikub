<?php

namespace App\Filament\Resources\SupportTicketResource\Widgets;

use App\Models\SupportTicket;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Card;

class TicketStatsWidget extends BaseWidget
{
    public ?SupportTicket $record = null;

    protected function getRecord(): ?SupportTicket
    {
        return $this->record ?? $this->getOwner()?->record;
    }

    protected function getCards(): array
    {
        $ticket = $this->getRecord();

        if (! $ticket) {
            return [];
        }

        // Время в работе
        $timeInWork = $ticket->created_at->diffInMinutes(now());
        $timeInWorkFormatted = $timeInWork > 60
            ? round($timeInWork / 60, 1).' ч'
            : $timeInWork.' мин';

        // Время до решения (если решён)
        $resolutionTime = null;
        if ($ticket->resolved_at) {
            $resolutionTime = $ticket->created_at->diffInMinutes($ticket->resolved_at);
            $resolutionTimeFormatted = $resolutionTime > 60
                ? round($resolutionTime / 60, 1).' ч'
                : $resolutionTime.' мин';
        }

        // Количество сообщений
        $messagesCount = $ticket->messages()->count();

        // Непрочитанные сообщения от работника
        $unreadFromWorker = $ticket->messages()
            ->where('sender_type', 'worker')
            ->whereNull('read_at')
            ->count();

        return [
            Card::make('Время в работе', $timeInWorkFormatted)
                ->description($ticket->isResolved() ? 'Тикет закрыт' : 'С момента создания')
                ->descriptionIcon('heroicon-o-clock')
                ->color($ticket->isResolved() ? 'success' : ($ticket->priority === 'urgent' ? 'danger' : 'warning')),
            Card::make('Время решения', $resolutionTimeFormatted ?? '—')
                ->description($ticket->isResolved() ? 'От создания до закрытия' : 'Тикет ещё не решён')
                ->descriptionIcon('heroicon-o-check-circle')
                ->color($ticket->isResolved() ? 'success' : 'gray'),
            Card::make('Сообщений', $messagesCount)
                ->description($unreadFromWorker > 0 ? "{$unreadFromWorker} непрочитанных" : 'Все прочитаны')
                ->descriptionIcon('heroicon-o-chat-alt')
                ->color($unreadFromWorker > 0 ? 'warning' : 'success'),
            Card::make('Приоритет', match ($ticket->priority) {
                'urgent' => 'Срочный',
                'high' => 'Высокий',
                'normal' => 'Обычный',
                'low' => 'Низкий',
                default => $ticket->priority,
            })
                ->description('Уровень важности')
                ->descriptionIcon('heroicon-o-flag')
                ->color(match ($ticket->priority) {
                    'urgent' => 'danger',
                    'high' => 'warning',
                    'normal' => 'primary',
                    'low' => 'gray',
                    default => 'gray',
                }),
        ];
    }
}
