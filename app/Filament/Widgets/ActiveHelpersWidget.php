<?php

namespace App\Filament\Widgets;

use App\Models\CareOrderDetails;
use App\Models\SocialHelperProfile;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ActiveHelpersWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $today = now()->toDateString();

        $activeToday = CareOrderDetails::query()
            ->whereDate('scheduled_start_at', $today)
            ->whereNotNull('assigned_helper_id')
            ->distinct('assigned_helper_id')
            ->count('assigned_helper_id');

        $totalActive = SocialHelperProfile::query()
            ->where('is_active', true)
            ->count();

        $withVisitsToday = SocialHelperProfile::query()
            ->where('is_active', true)
            ->whereHas('careOrders', function ($q) use ($today) {
                $q->whereDate('scheduled_start_at', $today);
            })
            ->count();

        return [
            Stat::make('Активных помощников', $totalActive)
                ->description('Всего в системе')
                ->descriptionIcon('heroicon-o-users')
                ->color('primary'),

            Stat::make('С визитами сегодня', $withVisitsToday)
                ->description('Имеют назначенные визиты')
                ->descriptionIcon('heroicon-o-calendar')
                ->color('info'),

            Stat::make('Назначено на сегодня', $activeToday)
                ->description('Уникальных помощников')
                ->descriptionIcon('heroicon-o-check')
                ->color('success'),
        ];
    }
}
