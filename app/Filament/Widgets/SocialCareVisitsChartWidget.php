<?php

namespace App\Filament\Widgets;

use App\Services\SocialCare\SocialCareAnalyticsService;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class SocialCareVisitsChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Визиты и часы по дням';

    protected static ?int $sort = 10;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $pollingInterval = '30s';

    protected static ?string $maxHeight = '300px';

    public ?string $periodPreset = '30d';

    public ?string $helperLevel = null;

    public ?int $careServiceId = null;

    public ?string $city = null;

    protected function getData(): array
    {
        $analytics = app(SocialCareAnalyticsService::class);
        [$from, $to] = $this->resolvePeriod();

        $data = $analytics->visitsAndHoursByDay($from, $to, $this->helperLevel, $this->careServiceId, $this->city);

        $labels = [];
        $visitsData = [];
        $hoursData = [];

        $current = $from->copy()->startOfDay();
        while ($current <= $to) {
            $dateStr = $current->toDateString();
            $labels[] = $current->format('d.m');

            $point = $data->firstWhere('date', $dateStr);
            $visitsData[] = $point ? (int) $point['visits_count'] : 0;
            $hoursData[] = $point ? (float) $point['total_hours'] : 0;

            $current->addDay();
        }

        return [
            'datasets' => [
                [
                    'label' => 'Количество визитов',
                    'data' => $visitsData,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.5)',
                    'borderColor' => 'rgb(59, 130, 246)',
                    'yAxisID' => 'y',
                ],
                [
                    'label' => 'Часы помощи',
                    'data' => $hoursData,
                    'backgroundColor' => 'rgba(34, 197, 94, 0.5)',
                    'borderColor' => 'rgb(34, 197, 94)',
                    'yAxisID' => 'y1',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'left',
                    'title' => [
                        'display' => true,
                        'text' => 'Визиты',
                    ],
                ],
                'y1' => [
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'right',
                    'title' => [
                        'display' => true,
                        'text' => 'Часы',
                    ],
                    'grid' => [
                        'drawOnChartArea' => false,
                    ],
                ],
            ],
        ];
    }

    protected function resolvePeriod(): array
    {
        $to = now();
        switch ($this->periodPreset) {
            case 'today':
                $from = $to->copy()->startOfDay();
                break;
            case '7d':
                $from = $to->copy()->subDays(7);
                break;
            case 'quarter':
                $from = $to->copy()->startOfQuarter();
                break;
            case 'year':
                $from = $to->copy()->startOfYear();
                break;
            case 'all':
                $from = Carbon::minValue();
                break;
            case '30d':
            default:
                $from = $to->copy()->subDays(30);
                break;
        }

        return [$from, $to];
    }
}
