<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RoadsideStatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $roadsideQuery = Order::query()
            ->where(function ($q) {
                $q->whereHas('roadsideDetails')
                    ->orWhereHas('roadsideEmergency')
                    ->orWhereHas('vehicleInspection')
                    ->orWhereHas('orderItems.serviceType', function ($sq) {
                        $sq->where(function ($q) {
                            $q->whereIn('code', ['roadside_assistance', 'vehicle_inspection', 'vehicle_transport'])
                                ->orWhereIn('category', ['roadside_assistance', 'vehicle_inspection', 'vehicle_transport']);
                        });
                    });
            });

        // Active count
        $activeCount = (clone $roadsideQuery)
            ->whereIn('status', ['pending', 'assigned', 'in_progress'])
            ->count();

        // Completed today
        $completedToday = (clone $roadsideQuery)
            ->whereIn('status', ['completed', 'delivered'])
            ->whereDate('completed_at', today())
            ->count();

        // Partner share
        $totalRoadside = (clone $roadsideQuery)->count();
        $partnerCount = (clone $roadsideQuery)
            ->whereHas('roadsideDetails', function ($q) {
                $q->whereNotNull('partner_id');
            })
            ->count();

        $partnerShare = $totalRoadside > 0
            ? round(($partnerCount / $totalRoadside) * 100, 1)
            : 0;

        // Average response time (from created_at to assigned/in_progress)
        $avgResponseTime = (clone $roadsideQuery)
            ->whereIn('status', ['assigned', 'in_progress', 'completed'])
            ->whereNotNull('started_at')
            ->selectRaw('AVG(EXTRACT(EPOCH FROM (started_at - created_at)) / 60) as avg_minutes')
            ->value('avg_minutes');

        $avgResponseTimeFormatted = $avgResponseTime
            ? round($avgResponseTime, 0).' мин'
            : '—';

        return [
            Stat::make('Активные roadside-заказы', $activeCount)
                ->description('pending, assigned, in_progress')
                ->descriptionIcon('heroicon-o-clock')
                ->color($activeCount > 0 ? 'warning' : 'success'),

            Stat::make('Завершено сегодня', $completedToday)
                ->description('За сегодня')
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success'),

            Stat::make('Доля партнёров', $partnerShare.'%')
                ->description("{$partnerCount} из {$totalRoadside}")
                ->descriptionIcon('heroicon-o-truck')
                ->color('info'),

            Stat::make('Среднее время отклика', $avgResponseTimeFormatted)
                ->description('От создания до начала работы')
                ->descriptionIcon('heroicon-o-lightning-bolt')
                ->color('primary'),
        ];
    }
}
