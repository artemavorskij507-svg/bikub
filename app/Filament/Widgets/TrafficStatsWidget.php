<?php

namespace App\Filament\Widgets;

use App\Models\TrafficIncident;
use App\Models\TravelTime;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TrafficStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $now = Carbon::now();

        // Active incidents (currently active)
        $activeIncidents = TrafficIncident::where(function ($query) use ($now) {
            $query->where('status', 'active')
                ->where(function ($q) use ($now) {
                    $q->whereNull('ends_at')
                        ->orWhere('ends_at', '>', $now);
                });
        })->count();

        // High severity incidents
        $highSeverity = TrafficIncident::where('severity', 'high')
            ->where(function ($q) use ($now) {
                $q->whereNull('ends_at')
                    ->orWhere('ends_at', '>', $now);
            })->count();

        // Recent incidents (last 24 hours)
        $recentIncidents = TrafficIncident::where('created_at', '>=', $now->copy()->subDay())->count();

        // Active routes (reset now for next queries)
        $now2 = Carbon::now();
        $activeRoutes = TravelTime::whereNotNull('measured_at')
            ->where('measured_at', '>=', $now2->copy()->subHours(2))
            ->count();

        // Delayed routes
        $delayedRoutes = TravelTime::where('status', 'delayed')
            ->where('measured_at', '>=', $now2->copy()->subHours(2))
            ->count();

        // Average travel time (for active routes)
        $avgTime = TravelTime::where('measured_at', '>=', $now2->copy()->subHours(2))
            ->avg('travel_time_seconds');
        $avgTimeMinutes = $avgTime ? round($avgTime / 60, 1) : 0;

        return [
            Stat::make('Активные инциденты', $activeIncidents)
                ->description('В данный момент')
                ->descriptionIcon('heroicon-o-exclamation')
                ->color($activeIncidents > 0 ? 'danger' : 'success'),

            Stat::make('Высокий приоритет', $highSeverity)
                ->description('Критические инциденты')
                ->descriptionIcon('heroicon-o-exclamation-circle')
                ->color('danger'),

            Stat::make('За 24 часа', $recentIncidents)
                ->description('Новых инцидентов')
                ->descriptionIcon('heroicon-o-clock')
                ->color('info'),

            Stat::make('Активных маршрутов', $activeRoutes)
                ->description('Обновлено за 2 часа')
                ->descriptionIcon('heroicon-o-map')
                ->color('primary'),

            Stat::make('Задержки', $delayedRoutes)
                ->description('Маршрутов с задержками')
                ->descriptionIcon('heroicon-o-clock')
                ->color($delayedRoutes > 0 ? 'warning' : 'success'),

            Stat::make('Среднее время', $avgTimeMinutes.' мин')
                ->description('Среднее время в пути')
                ->descriptionIcon('heroicon-o-refresh')
                ->color('info'),
        ];
    }
}
