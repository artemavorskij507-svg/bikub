<?php

namespace App\Filament\Widgets;

use App\Models\Employee;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ActiveCouriersWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected function getStats(): array
    {
        $online = Employee::where('is_online', true)
            ->where('status', 'active')
            ->where('last_ping_at', '>=', now()->subMinutes(2))
            ->count();

        $total = Employee::where('status', 'active')->count();

        $withTasks = Employee::whereHas('tasks', function ($q) {
            $q->whereIn('status', ['assigned', 'en_route', 'arrived', 'in_progress']);
        })
            ->where('status', 'active')
            ->count();

        $available = Employee::where('is_online', true)
            ->where('status', 'active')
            ->whereDoesntHave('tasks', function ($q) {
                $q->whereIn('status', ['assigned', 'en_route', 'arrived', 'in_progress']);
            })
            ->count();

        return [
            Stat::make('Онлайн курьеров', $online)
                ->description("Всего активных: {$total}")
                ->color($online > 0 ? 'success' : 'gray')
                ->icon('heroicon-o-user-group'),
            Stat::make('С задачами', $withTasks)
                ->description('Выполняют задания')
                ->color('warning')
                ->icon('heroicon-o-shopping-bag'),
            Stat::make('Свободных', $available)
                ->description('Готовы к назначению')
                ->color('info')
                ->icon('heroicon-o-badge-check'),
        ];
    }
}
