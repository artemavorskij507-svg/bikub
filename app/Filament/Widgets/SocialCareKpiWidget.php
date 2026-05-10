<?php

namespace App\Filament\Widgets;

use App\Services\SocialCare\SocialCareAnalyticsService;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SocialCareKpiWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';

    public ?string $periodPreset = '30d';

    public ?string $helperLevel = null;

    public ?int $careServiceId = null;

    public ?string $city = null;

    protected function getStats(): array
    {
        $analytics = app(SocialCareAnalyticsService::class);
        [$from, $to] = $this->resolvePeriod();

        $kpi = $analytics->aggregateKpi($from, $to, $this->helperLevel, $this->careServiceId, $this->city);

        return [
            Stat::make('Часы помощи', number_format($kpi['total_hours'], 1, ',', ' '))
                ->description('Оказано за период')
                ->descriptionIcon('heroicon-o-clock')
                ->color('primary'),

            Stat::make('Уникальных клиентов', number_format($kpi['unique_clients'], 0, ',', ' '))
                ->description('Получили помощь')
                ->descriptionIcon('heroicon-o-user-group')
                ->color('info'),

            Stat::make('Активных Care Plans', number_format($kpi['active_care_plans'], 0, ',', ' '))
                ->description('Регулярный уход')
                ->descriptionIcon('heroicon-o-heart')
                ->color('success'),

            Stat::make('Всего визитов', number_format($kpi['total_visits'], 0, ',', ' '))
                ->description('Завершённых визитов')
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('warning'),

            Stat::make('Волонтёрские часы', number_format($kpi['volunteer_hours'], 1, ',', ' '))
                ->description('Community/Friend')
                ->descriptionIcon('heroicon-o-star')
                ->color('success'),
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
