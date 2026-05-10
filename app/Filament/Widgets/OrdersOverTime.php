<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class OrdersOverTime extends ChartWidget
{
    protected static ?string $heading = 'Заказы по дням (30d)';

    protected function getData(): array
    {
        $rows = DB::table('orders')
            ->selectRaw('DATE(created_at) as d, COUNT(*) as c, SUM(total_amount) as gmv')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('d')
            ->orderBy('d')
            ->get();

        return [
            'labels' => $rows->pluck('d')->map(fn ($d) => date('M d', strtotime($d))),
            'datasets' => [
                ['label' => 'Заказы', 'data' => $rows->pluck('c')],
                ['label' => 'GMV, NOK', 'data' => $rows->pluck('gmv')],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
