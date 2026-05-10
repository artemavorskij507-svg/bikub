<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OrderStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $today = Carbon::today();
        $thisMonth = Carbon::now()->startOfMonth();

        return [
            Stat::make('Всего заказов', Order::count())
                ->description('За все время')
                ->descriptionIcon('heroicon-o-arrow-up')
                ->color('success'),

            Stat::make('Заказы сегодня', Order::whereDate('created_at', $today)->count())
                ->description('За сегодня')
                ->descriptionIcon('heroicon-o-calendar')
                ->color('primary'),

            Stat::make('Заказы за месяц', Order::where('created_at', '>=', $thisMonth)->count())
                ->description('За текущий месяц')
                ->descriptionIcon('heroicon-o-chart-bar')
                ->color('info'),

            Stat::make('Выручка за месяц', number_format(Order::where('payment_status', 'paid')
                ->where('created_at', '>=', $thisMonth)
                ->sum('total_amount'), 0, ',', ' ').' NOK')
                ->description('Оплаченные заказы')
                ->descriptionIcon('heroicon-o-currency-dollar')
                ->color('success'),

            Stat::make('Активные задачи', Task::whereIn('status', ['queued', 'assigned', 'enroute'])->count())
                ->description('В работе')
                ->descriptionIcon('heroicon-o-shopping-bag')
                ->color('warning'),

            Stat::make('Исполнители', User::whereHas('roles', function ($query) {
                $query->where('name', 'courier');
            })->where('is_active', true)->count())
                ->description('Активные курьеры')
                ->descriptionIcon('heroicon-o-users')
                ->color('secondary'),
        ];
    }
}
