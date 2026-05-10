<?php

namespace App\Filament\Widgets\Analytics;

use App\Models\Order;
use App\Models\Task;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AnalyticsStatsWidget extends BaseWidget
{
    public ?string $filter = '30days';

    protected static ?string $pollingInterval = '30s';

    protected function getFilters(): ?array
    {
        return [
            '7days' => '7 дней',
            '30days' => '30 дней',
            '90days' => '90 дней',
            'year' => 'Год',
        ];
    }

    protected function getStats(): array
    {
        $days = match ($this->filter) {
            '7days' => 7,
            '30days' => 30,
            '90days' => 90,
            'year' => 365,
            default => 30,
        };

        $startDate = Carbon::now()->subDays($days);
        $previousStartDate = Carbon::now()->subDays($days * 2);
        $previousEndDate = $startDate->copy();

        // Заказы
        $ordersCount = Order::where('created_at', '>=', $startDate)->count();
        $previousOrdersCount = Order::whereBetween('created_at', [$previousStartDate, $previousEndDate])->count();
        $ordersChange = $previousOrdersCount > 0
            ? round((($ordersCount - $previousOrdersCount) / $previousOrdersCount) * 100, 1)
            : 0;

        // Выручка
        $revenue = Order::where('payment_status', 'paid')
            ->where('created_at', '>=', $startDate)
            ->sum('total_amount');
        $previousRevenue = Order::where('payment_status', 'paid')
            ->whereBetween('created_at', [$previousStartDate, $previousEndDate])
            ->sum('total_amount');
        $revenueChange = $previousRevenue > 0
            ? round((($revenue - $previousRevenue) / $previousRevenue) * 100, 1)
            : 0;

        // Средний чек
        $avgOrderValue = $ordersCount > 0 ? $revenue / $ordersCount : 0;
        $previousAvgOrderValue = $previousOrdersCount > 0 ? $previousRevenue / $previousOrdersCount : 0;
        $avgChange = $previousAvgOrderValue > 0
            ? round((($avgOrderValue - $previousAvgOrderValue) / $previousAvgOrderValue) * 100, 1)
            : 0;

        // Завершенные задачи
        $completedTasks = Task::where('status', 'completed')
            ->where('created_at', '>=', $startDate)
            ->count();
        $previousCompletedTasks = Task::where('status', 'completed')
            ->whereBetween('created_at', [$previousStartDate, $previousEndDate])
            ->count();
        $tasksChange = $previousCompletedTasks > 0
            ? round((($completedTasks - $previousCompletedTasks) / $previousCompletedTasks) * 100, 1)
            : 0;

        return [
            Stat::make('Заказов', number_format($ordersCount, 0, ',', ' '))
                ->description($ordersChange >= 0 ? "+{$ordersChange}%" : "{$ordersChange}%")
                ->descriptionIcon($ordersChange >= 0 ? 'heroicon-o-arrow-up' : 'heroicon-o-arrow-down')
                ->color($ordersChange >= 0 ? 'success' : 'danger'),

            Stat::make('Выручка', number_format($revenue, 0, ',', ' ').' NOK')
                ->description($revenueChange >= 0 ? "+{$revenueChange}%" : "{$revenueChange}%")
                ->descriptionIcon($revenueChange >= 0 ? 'heroicon-o-arrow-up' : 'heroicon-o-arrow-down')
                ->color($revenueChange >= 0 ? 'success' : 'danger'),

            Stat::make('Средний чек', number_format($avgOrderValue, 0, ',', ' ').' NOK')
                ->description($avgChange >= 0 ? "+{$avgChange}%" : "{$avgChange}%")
                ->descriptionIcon($avgChange >= 0 ? 'heroicon-o-arrow-up' : 'heroicon-o-arrow-down')
                ->color($avgChange >= 0 ? 'success' : 'warning'),

            Stat::make('Завершено задач', number_format($completedTasks, 0, ',', ' '))
                ->description($tasksChange >= 0 ? "+{$tasksChange}%" : "{$tasksChange}%")
                ->descriptionIcon($tasksChange >= 0 ? 'heroicon-o-arrow-up' : 'heroicon-o-arrow-down')
                ->color($tasksChange >= 0 ? 'success' : 'danger'),
        ];
    }
}
