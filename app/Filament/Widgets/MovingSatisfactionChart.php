<?php

namespace App\Filament\Widgets;

use App\Models\Moving\MovingOrder;
use Carbon\CarbonPeriod;
use Filament\Widgets\ChartWidget;

class MovingSatisfactionChart extends ChartWidget
{
    protected static ?string $heading = 'NPS переїздів';

    protected static ?string $maxHeight = '300px';

    protected function getData(): array
    {
        try {
            $data = MovingOrder::query()
                ->whereNotNull('nps_score')
                ->selectRaw('DATE(created_at) as date, AVG(nps_score) as avg_score')
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->pluck('avg_score', 'date');

            $period = CarbonPeriod::create(now()->subDays(14)->startOfDay(), now()->startOfDay());

            $labels = [];
            $values = [];

            foreach ($period as $date) {
                $formatted = $date->format('Y-m-d');
                $labels[] = $date->format('d.m');
                $values[] = round((float) ($data[$formatted] ?? 0), 2);
            }

            return [
                'datasets' => [
                    [
                        'label' => 'Середній NPS',
                        'data' => $values,
                        'borderColor' => '#2563eb',
                        'backgroundColor' => 'rgba(37, 99, 235, 0.2)',
                        'tension' => 0.4,
                        'fill' => true,
                    ],
                ],
                'labels' => $labels,
            ];
        } catch (\Exception $e) {
            // Return empty chart data on error
            $period = CarbonPeriod::create(now()->subDays(14)->startOfDay(), now()->startOfDay());
            $labels = [];
            $values = [];
            foreach ($period as $date) {
                $labels[] = $date->format('d.m');
                $values[] = 0;
            }

            return [
                'datasets' => [
                    [
                        'label' => 'Середній NPS',
                        'data' => $values,
                        'borderColor' => '#2563eb',
                        'backgroundColor' => 'rgba(37, 99, 235, 0.2)',
                        'tension' => 0.4,
                        'fill' => true,
                    ],
                ],
                'labels' => $labels,
            ];
        }
    }

    protected function getType(): string
    {
        return 'line';
    }
}
