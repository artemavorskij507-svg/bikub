<?php

namespace App\Filament\Widgets;

use App\Models\Moving\MovingOrder;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class MovingStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $today = Carbon::today();
        $thisMonth = Carbon::now()->startOfMonth();
        $thisWeek = Carbon::now()->startOfWeek();

        // Всего заказов
        $totalOrders = MovingOrder::count();

        // Заказы сегодня
        $todayOrders = MovingOrder::whereDate('created_at', $today)->count();

        // Заказы за месяц
        $monthOrders = MovingOrder::where('created_at', '>=', $thisMonth)->count();

        // Активные заказы (pending, confirmed, in_progress)
        $activeOrders = MovingOrder::whereIn('status', ['pending', 'confirmed', 'in_progress'])->count();

        // Завершенные за этот месяц
        $completedThisMonth = MovingOrder::where('status', 'completed')
            ->where('created_at', '>=', $thisMonth)
            ->count();

        // Выручка за месяц (финальная цена завершенных)
        $revenue = MovingOrder::where('status', 'completed')
            ->where('created_at', '>=', $thisMonth)
            ->sum('final_price') ?: MovingOrder::where('status', 'completed')
            ->where('created_at', '>=', $thisMonth)
            ->sum('estimated_price');

        // Средняя цена заказа
        $avgPrice = MovingOrder::where('status', 'completed')
            ->where('created_at', '>=', $thisMonth)
            ->avg(DB::raw('COALESCE(final_price, estimated_price)'));

        // Заказы без назначенной бригады
        $withoutTeam = MovingOrder::whereIn('status', ['pending', 'confirmed'])
            ->whereNull('executor_team_id')
            ->count();

        // Общий объем за месяц
        $totalVolume = MovingOrder::where('created_at', '>=', $thisMonth)
            ->sum('total_volume');

        return [
            Stat::make('Всього замовлень', $totalOrders)
                ->description('За весь час')
                ->descriptionIcon('heroicon-o-truck')
                ->color('success'),

            Stat::make('Сьогодні', $todayOrders)
                ->description('Нові замовлення')
                ->descriptionIcon('heroicon-o-calendar')
                ->color('primary'),

            Stat::make('Активні', $activeOrders)
                ->description('В роботі')
                ->descriptionIcon('heroicon-o-clock')
                ->color('warning'),

            Stat::make('Виручка за місяць', number_format($revenue, 0, ',', ' ').' NOK')
                ->description('Завершені замовлення')
                ->descriptionIcon('heroicon-o-currency-dollar')
                ->color('success'),

            Stat::make('Без бригади', $withoutTeam)
                ->description('Потребують призначення')
                ->descriptionIcon('heroicon-o-exclamation-circle')
                ->color($withoutTeam > 0 ? 'danger' : 'success'),

            Stat::make('Обʼєм за місяць', number_format($totalVolume, 1, ',', ' ').' м³')
                ->description('Загальний обʼєм')
                ->descriptionIcon('heroicon-o-cube')
                ->color('info'),
        ];
    }
}
