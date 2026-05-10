<?php

namespace App\Filament\Widgets;

use App\Services\SocialCare\SocialCareAnalyticsService;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class SocialCareServicesChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Распределение по типам услуг';

    protected static ?int $sort = 20;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $pollingInterval = '30s';

    protected static ?string $maxHeight = '300px';

    public ?string $periodPreset = '30d';

    public ?string $helperLevel = null;

    public ?string $city = null;

    protected function getData(): array
    {
        $analytics = app(SocialCareAnalyticsService::class);
        [$from, $to] = $this->resolvePeriod();

        $data = $analytics->servicesDistribution($from, $to, $this->helperLevel, $this->city);

        $labels = $data->pluck('service_name')->toArray();
        $visitsData = $data->pluck('visits_count')->toArray();
        $hoursData = $data->pluck('total_hours')->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'Количество визитов',
                    'data' => $visitsData,
                    'backgroundColor' => [
                        'rgba(59, 130, 246, 0.8)',
                        'rgba(34, 197, 94, 0.8)',
                        'rgba(251, 191, 36, 0.8)',
                        'rgba(239, 68, 68, 0.8)',
                        'rgba(168, 85, 247, 0.8)',
                        'rgba(236, 72, 153, 0.8)',
                    ],
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
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
