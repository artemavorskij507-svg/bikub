<?php

namespace App\Filament\Widgets;

use App\Models\Task;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SLARiskWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        $now = now();

        $atRisk = Task::whereNotNull('sla_deadline_at')
            ->whereIn('status', ['assigned', 'en_route', 'arrived', 'in_progress'])
            ->whereRaw('sla_deadline_at <= ?', [$now->copy()->addMinutes(15)])
            ->count();

        $overdue = Task::whereNotNull('sla_deadline_at')
            ->whereIn('status', ['assigned', 'en_route', 'arrived', 'in_progress'])
            ->where('sla_deadline_at', '<', $now)
            ->count();

        $cancelled = Task::where('status', 'canceled')
            ->whereDate('updated_at', today())
            ->count();

        $refunded = Task::whereHas('order', function ($q) {
            $q->where('payment_status', 'refunded');
        })
            ->whereDate('updated_at', today())
            ->count();

        return [
            Stat::make('SLA под угрозой', $atRisk)
                ->description('Дедлайн через < 15 мин')
                ->color($atRisk > 0 ? 'danger' : 'success')
                ->icon('heroicon-o-exclamation'),
            Stat::make('Просрочено', $overdue)
                ->description('SLA нарушено')
                ->color($overdue > 0 ? 'danger' : 'success')
                ->icon('heroicon-o-x-circle'),
            Stat::make('Отмены сегодня', $cancelled)
                ->description('Отменённые задачи')
                ->color('gray')
                ->icon('heroicon-o-x'),
            Stat::make('Рефанды сегодня', $refunded)
                ->description('Возвраты платежей')
                ->color('warning')
                ->icon('heroicon-o-refresh'),
        ];
    }
}
