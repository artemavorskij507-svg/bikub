<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class SlotUtilization extends ChartWidget
{
    protected static ?string $heading = 'Загрузка слотов сегодня';

    protected function getData(): array
    {
        $rows = DB::table('schedule_slots as s')
            ->leftJoin('orders as o', function ($j) {
                $j->on('o.schedule_slot_id', '=', 's.id')
                    ->whereDate('o.created_at', now()->toDateString())
                    ->whereIn('o.status', ['in_progress', 'completed', 'assigned']);
            })
            ->selectRaw('s.geo_zone_id as zone_id, s.capacity_total as capacity, count(o.id) as used')
            ->whereDate('s.start_at', now()->toDateString())
            ->groupBy('s.geo_zone_id', 's.capacity_total')
            ->get();

        $labels = $rows->pluck('zone_id')->map(fn ($z) => "Зона {$z}");
        $used = $rows->pluck('used');
        $cap = $rows->pluck('capacity');

        return [
            'labels' => $labels,
            'datasets' => [
                ['label' => 'Занято', 'data' => $used],
                ['label' => 'Вместимость', 'data' => $cap],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
