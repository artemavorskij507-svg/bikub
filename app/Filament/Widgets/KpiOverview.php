<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget as Widget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class KpiOverview extends Widget
{
    protected function getStats(): array
    {
        $from = now()->subDays(30);

        // GMV: используем total_amount (в NOK). Если есть amount_minor, можно заменить.
        $gmv = (float) Order::where('status', 'completed')
            ->where('created_at', '>=', $from)
            ->sum('total_amount');

        $completed = Order::where('status', 'completed')
            ->where('created_at', '>=', $from)
            ->count();

        $canceled = Order::where('status', 'cancelled')
            ->where('created_at', '>=', $from)
            ->count();

        $onTime = DB::table('tasks')
            ->whereNotNull('completed_at')
            ->whereRaw('window_end >= completed_at')
            ->where('created_at', '>=', $from)
            ->count();

        $allDone = DB::table('tasks')
            ->whereNotNull('completed_at')
            ->where('created_at', '>=', $from)
            ->count();

        $onTimeRate = $allDone ? round(100 * $onTime / $allDone, 1) : 100;

        return [
            Stat::make('GMV (30d)', number_format($gmv, 0, ',', ' ').' NOK')->description('Выручка завершённых'),
            Stat::make('Завершено (30d)', (string) $completed)->description('Все заказы'),
            Stat::make('On-time (30d)', $onTimeRate.'%')->description('Доля в срок'),
            Stat::make('Отмены (30d)', (string) $canceled)->description('Всего отмен'),
        ];
    }
}
