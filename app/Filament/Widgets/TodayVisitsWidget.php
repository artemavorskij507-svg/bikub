<?php

namespace App\Filament\Widgets;

use App\Enums\CareOrderStatus;
use App\Models\CareOrderDetails;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TodayVisitsWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $today = now()->toDateString();

        $baseQuery = CareOrderDetails::query()
            ->whereDate('scheduled_start_at', $today);

        $total = (clone $baseQuery)->count();

        $pending = (clone $baseQuery)
            ->whereIn('care_status', [
                CareOrderStatus::PENDING->value,
                CareOrderStatus::SCHEDULED->value,
            ])
            ->count();

        $inProgress = (clone $baseQuery)
            ->where('care_status', CareOrderStatus::IN_PROGRESS->value)
            ->count();

        $completed = (clone $baseQuery)
            ->where('care_status', CareOrderStatus::COMPLETED->value)
            ->count();

        return [
            Stat::make('Визитов сегодня', $total)
                ->description('Всего запланировано')
                ->descriptionIcon('heroicon-o-calendar')
                ->color('primary'),

            Stat::make('Ожидают / Запланированы', $pending)
                ->description('Требуют внимания')
                ->descriptionIcon('heroicon-o-clock')
                ->color('warning'),

            Stat::make('Сейчас в работе', $inProgress)
                ->description('В процессе выполнения')
                ->descriptionIcon('heroicon-o-play')
                ->color('info'),

            Stat::make('Завершено', $completed)
                ->description('Успешно выполнено')
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success'),
        ];
    }
}
