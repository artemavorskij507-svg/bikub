<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RoadsideDashboardStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $activeQuery = Order::query()
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

        $activeCount = (clone $activeQuery)
            ->whereIn('status', ['pending', 'assigned', 'in_progress'])
            ->count();

        $completed24h = (clone $activeQuery)
            ->where('status', 'completed')
            ->where('created_at', '>=', Carbon::now()->subDay())
            ->count();

        $pendingCount = (clone $activeQuery)
            ->where('status', 'pending')
            ->count();

        return [
            Stat::make('Активные заказы', $activeCount)
                ->description('pending, assigned, in_progress')
                ->descriptionIcon('heroicon-o-clock')
                ->color('warning'),

            Stat::make('Ожидают назначения', $pendingCount)
                ->description('Требуют внимания')
                ->descriptionIcon('heroicon-o-exclamation-circle')
                ->color('danger'),

            Stat::make('Завершено за 24ч', $completed24h)
                ->description('За последние сутки')
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success'),
        ];
    }
}
