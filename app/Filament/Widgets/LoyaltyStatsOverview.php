<?php

namespace App\Filament\Widgets;

use App\Models\LoyaltyBalance;
use App\Models\LoyaltyTransaction;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Card;

class LoyaltyStatsOverview extends BaseWidget
{
    protected function getCards(): array
    {
        $totalBalances = LoyaltyBalance::count();
        $totalPoints = LoyaltyBalance::sum('points');
        $activeUsers = LoyaltyBalance::where('points', '>', 0)->count();
        $totalTransactions = LoyaltyTransaction::count();

        return [
            Card::make('Користувачів з балами', $totalBalances)
                ->description('Активні баланси лояльності')
                ->descriptionIcon('heroicon-o-users')
                ->icon('heroicon-o-user-group')
                ->color('info'),

            Card::make('Всього балів у системі', number_format($totalPoints, 0, '.', ' '))
                ->description('Поточні накопичені бали')
                ->descriptionIcon('heroicon-o-sparkles')
                ->icon('heroicon-o-gift')
                ->color('success'),

            Card::make('Користувачів з активними балами', $activeUsers)
                ->description('Мають мінімум 1 бал')
                ->descriptionIcon('heroicon-o-trending-up')
                ->icon('heroicon-o-chart-bar')
                ->color('warning'),

            Card::make('Операцій', $totalTransactions)
                ->description('Всього транзакцій в системі')
                ->descriptionIcon('heroicon-o-receipt-refund')
                ->icon('heroicon-o-document-duplicate')
                ->color('primary'),
        ];
    }
}
