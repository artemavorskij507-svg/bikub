<?php

namespace App\Filament\Widgets;

use App\Enums\ServiceType;
use App\Models\Claim;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OpenClaimsStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        try {
            $openClaims = Claim::whereIn('status', ['open', 'in_review'])->count();

            // Check through OrderItem service_type_id or Order service_type
            $handymanRepairClaims = Claim::whereIn('status', ['open', 'in_review'])
                ->whereHas('order', function ($q) {
                    $q->where(function ($query) {
                        // Check service_type field in Order
                        $query->whereIn('service_type', [
                            ServiceType::HANDYMAN_HOURLY->value ?? 'handyman_hourly',
                            ServiceType::HANDYMAN_FIXED->value ?? 'handyman_fixed',
                            ServiceType::COMPLEX_REPAIR->value ?? 'complex_repair',
                        ])
                        // Or check through OrderItem
                            ->orWhereHas('orderItems.serviceType', function ($sq) {
                                $sq->whereIn('category', ['handyman', 'repair']);
                            });
                    });
                })
                ->count();

            $deliveryClaims = Claim::whereIn('status', ['open', 'in_review'])
                ->whereHas('order', function ($q) {
                    $q->where(function ($query) {
                        // Check service_type field in Order
                        $query->whereIn('service_type', [
                            ServiceType::GROCERY_DELIVERY->value ?? 'grocery_delivery',
                        ])
                        // Or check through OrderItem
                            ->orWhereHas('orderItems.serviceType', function ($sq) {
                                $sq->whereIn('category', ['delivery', 'care']);
                            });
                    });
                })
                ->count();
        } catch (\Exception $e) {
            // Fallback if there are errors
            $openClaims = 0;
            $handymanRepairClaims = 0;
            $deliveryClaims = 0;
        }

        return [
            Stat::make('Открытые претензии (все)', $openClaims)
                ->description('Требуют внимания')
                ->descriptionIcon('heroicon-o-exclamation-circle')
                ->color('warning'),

            Stat::make('Мастер/Ремонт', $handymanRepairClaims)
                ->description('Претензии по мастеру и ремонту')
                ->descriptionIcon('heroicon-o-briefcase')
                ->color('danger'),

            Stat::make('Доставка', $deliveryClaims)
                ->description('Претензии по доставке')
                ->descriptionIcon('heroicon-o-truck')
                ->color('info'),
        ];
    }
}
