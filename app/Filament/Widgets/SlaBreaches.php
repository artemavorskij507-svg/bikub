<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class SlaBreaches extends ChartWidget
{
    protected static ?string $heading = 'SLA нарушения (30d)';

    protected function getData(): array
    {
        $rows = DB::table('tasks')
            ->selectRaw("coalesce(json_extract(meta,'$.breach_reason'),'unknown') as r, count(*) as c")
            ->whereNotNull('completed_at')
            ->whereColumn('completed_at', '>', 'window_end')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('r')
            ->get();

        return [
            'labels' => $rows->pluck('r'),
            'datasets' => [['label' => 'Нарушения', 'data' => $rows->pluck('c')]],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
