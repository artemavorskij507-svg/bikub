<?php

namespace App\Filament\Widgets;

use App\Models\Task;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TasksQueueWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $queued = Task::where('status', 'queued')->count();
        $ready = Task::where('status', 'ready')->count();
        $inWork = Task::whereIn('status', ['assigned', 'en_route', 'arrived', 'in_progress'])->count();
        $completedToday = Task::where('status', 'completed')
            ->whereDate('updated_at', today())
            ->count();

        return [
            Stat::make('В очереди', $queued)
                ->description('Задач ожидают назначения')
                ->color('gray')
                ->icon('heroicon-o-clock'),
            Stat::make('Готовы', $ready)
                ->description('Готовы к назначению')
                ->color('info')
                ->icon('heroicon-o-check-circle'),
            Stat::make('В работе', $inWork)
                ->description('Активно выполняются')
                ->color('warning')
                ->icon('heroicon-o-refresh'),
            Stat::make('Завершено сегодня', $completedToday)
                ->description('Успешно выполнено')
                ->color('success')
                ->icon('heroicon-o-check'),
        ];
    }
}
