<?php

namespace App\Filament\Widgets;

use App\Models\HandymanKpiSnapshot;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class HandymanQualityOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        try {
            $q = HandymanKpiSnapshot::query();

            $avgRating = round((float) $q->avg('avg_rating'), 2);
            $avgOnTime = round((float) $q->avg('on_time_rate'), 2);
            $totalClaims = (int) $q->sum('claims_count');
            $totalExecutors = $q->count();
            $avgQualityScore = round((float) $q->avg('quality_score'), 0);
        } catch (\Exception $e) {
            // Fallback if table doesn't exist or has errors
            $avgRating = 0;
            $avgOnTime = 0;
            $totalClaims = 0;
            $totalExecutors = 0;
            $avgQualityScore = 0;
        }

        return [
            Stat::make('Средний рейтинг мастеров', $avgRating ?: '—')
                ->description('Из '.$totalExecutors.' мастеров')
                ->descriptionIcon('heroicon-o-star')
                ->color('success'),

            Stat::make('Средний on-time %', $avgOnTime ? $avgOnTime.'%' : '—')
                ->description('Своевременность выполнения')
                ->descriptionIcon('heroicon-o-clock')
                ->color('info'),

            Stat::make('Всего претензий по мастерам', $totalClaims)
                ->description('Требуют внимания')
                ->descriptionIcon('heroicon-o-exclamation-circle')
                ->color($totalClaims > 0 ? 'warning' : 'success'),

            Stat::make('Средний Quality Score', $avgQualityScore ?: '—')
                ->description('Общий показатель качества')
                ->descriptionIcon('heroicon-o-chart-bar')
                ->color($avgQualityScore >= 80 ? 'success' : ($avgQualityScore >= 60 ? 'warning' : 'danger')),
        ];
    }
}
