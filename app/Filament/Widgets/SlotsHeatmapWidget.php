<?php

namespace App\Filament\Widgets;

use App\Models\ScheduleSlot;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Schema;

class SlotsHeatmapWidget extends ChartWidget
{
    protected static ?string $heading = 'Заполненность слотов по зонам';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        if (! Schema::hasTable((new ScheduleSlot)->getTable())) {
            return $this->emptyDataset();
        }

        $slots = ScheduleSlot::with('zone')
            ->get()
            ->groupBy(function ($slot) {
                return $slot->zone ? $slot->zone->name : 'Без зоны';
            });

        $zones = [];
        $utilization = [];
        $overbooked = [];

        foreach ($slots as $zoneName => $zoneSlots) {
            $zones[] = $zoneName;

            $totalCapacity = $zoneSlots->sum('capacity') ?: 1;
            $totalBooked = $zoneSlots->sum('booked');

            $utilizationPercent = ($totalCapacity > 0)
                ? round(($totalBooked / $totalCapacity) * 100, 1)
                : 0;

            $utilization[] = min(100, $utilizationPercent);

            // Count overbooked slots
            $overbookedCount = $zoneSlots->filter(function ($slot) {
                return $slot->isOverbooked();
            })->count();

            $overbooked[] = $overbookedCount;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Использование (%)',
                    'data' => $utilization,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.5)',
                    'borderColor' => 'rgb(59, 130, 246)',
                ],
                [
                    'label' => 'Переполненные слоты',
                    'data' => $overbooked,
                    'backgroundColor' => 'rgba(239, 68, 68, 0.5)',
                    'borderColor' => 'rgb(239, 68, 68)',
                    'yAxisID' => 'y1',
                ],
            ],
            'labels' => $zones,
        ];
    }

    private function emptyDataset(): array
    {
        return [
            'datasets' => [
                [
                    'label' => 'Использование (%)',
                    'data' => [],
                    'backgroundColor' => 'rgba(59, 130, 246, 0.5)',
                    'borderColor' => 'rgb(59, 130, 246)',
                ],
                [
                    'label' => 'Переполненные слоты',
                    'data' => [],
                    'backgroundColor' => 'rgba(239, 68, 68, 0.5)',
                    'borderColor' => 'rgb(239, 68, 68)',
                    'yAxisID' => 'y1',
                ],
            ],
            'labels' => [],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'max' => 100,
                    'title' => [
                        'display' => true,
                        'text' => 'Использование (%)',
                    ],
                ],
                'y1' => [
                    'position' => 'right',
                    'beginAtZero' => true,
                    'title' => [
                        'display' => true,
                        'text' => 'Количество переполненных',
                    ],
                    'grid' => [
                        'drawOnChartArea' => false,
                    ],
                ],
            ],
            'plugins' => [
                'legend' => [
                    'display' => true,
                ],
                'tooltip' => [
                    'enabled' => true,
                ],
            ],
        ];
    }
}
