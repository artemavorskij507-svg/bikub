<?php

namespace App\Filament\Resources\PriceEstimateLogResource\Widgets;

use App\Models\PriceEstimateLog;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Card;

class PriceEstimateLogsOverviewWidget extends BaseWidget
{
    protected function getCards(): array
    {
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();
        $thisWeek = Carbon::now()->startOfWeek();
        $thisMonth = Carbon::now()->startOfMonth();

        $totalLogs = PriceEstimateLog::count();
        $todayLogs = PriceEstimateLog::whereDate('created_at', $today)->count();
        $thisWeekLogs = PriceEstimateLog::where('created_at', '>=', $thisWeek)->count();
        $thisMonthLogs = PriceEstimateLog::where('created_at', '>=', $thisMonth)->count();

        $avgTotal = PriceEstimateLog::avg('total') ?? 0;
        $avgDuration = PriceEstimateLog::avg('duration_ms') ?? 0;
        $totalRevenue = PriceEstimateLog::sum('total') ?? 0;

        $uniqueUsers = PriceEstimateLog::whereNotNull('user_id')
            ->distinct('user_id')
            ->count('user_id');
        $guestLogs = PriceEstimateLog::whereNull('user_id')->count();

        return [
            Card::make('Всего запросов', $totalLogs)
                ->description('За всё время')
                ->descriptionIcon('heroicon-o-chart-bar')
                ->color('primary'),
            Card::make('Сегодня', $todayLogs)
                ->description('За последние 24 часа')
                ->descriptionIcon('heroicon-o-clock')
                ->color('info'),
            Card::make('Средняя сумма', number_format($avgTotal, 2, ',', ' ').' NOK')
                ->description('По всем запросам')
                ->descriptionIcon('heroicon-o-currency-dollar')
                ->color('success'),
            Card::make('Среднее время', $avgDuration < 1000
                ? number_format($avgDuration).' мс'
                : number_format($avgDuration / 1000, 2).' сек')
                ->description('Время выполнения')
                ->descriptionIcon('heroicon-o-lightning-bolt')
                ->color($avgDuration < 500 ? 'success' : ($avgDuration < 1000 ? 'warning' : 'danger')),
            Card::make('Уникальных пользователей', $uniqueUsers)
                ->description($guestLogs > 0 ? "Гостей: {$guestLogs}" : 'Все авторизованы')
                ->descriptionIcon('heroicon-o-users')
                ->color('info'),
            Card::make('Общая сумма', number_format($totalRevenue, 2, ',', ' ').' NOK')
                ->description('Сумма всех расчётов')
                ->descriptionIcon('heroicon-o-currency-dollar')
                ->color('success'),
        ];
    }
}
