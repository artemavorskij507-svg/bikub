<?php

namespace App\Filament\Widgets;

use App\Modules\Classifieds\Models\ClassifiedAd;
use App\Modules\Classifieds\Models\Shop;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class ClassifiedsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $newAdsToday = ClassifiedAd::whereDate('created_at', Carbon::today())->count();
        $totalActive = ClassifiedAd::published()->count();
        $revenue = \App\Modules\Classifieds\Models\AdPayment::whereMonth('created_at', Carbon::now()->month)->sum('amount');
        $activeShops = Shop::where('is_active', true)->count();

        return [
            Stat::make('New Ads Today', $newAdsToday)
                ->description('ADS created in last 24h')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),
            Stat::make('Active Listings', $totalActive)
                ->description('Total published ads')
                ->color('primary'),
            Stat::make('Shops', $activeShops)
                ->description('Business Accounts')
                ->color('warning'),
            Stat::make('Revenue (Month)', number_format($revenue, 2).' NOK')
                ->description('From promotions')
                ->chart([7, 10, 15, 8, 12, 20, $revenue]) // Dummy trend for visual
                ->color('success'),
        ];
    }
}
