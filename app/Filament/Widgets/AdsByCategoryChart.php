<?php

namespace App\Filament\Widgets;

use App\Modules\Classifieds\Models\ClassifiedAd;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class AdsByCategoryChart extends ChartWidget
{
    protected static ?string $heading = 'Ads by Category';

    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $data = ClassifiedAd::published()
            ->join('ad_categories', 'classified_ads.category_id', '=', 'ad_categories.id')
            ->select('ad_categories.name', DB::raw('count(*) as total'))
            ->groupBy('ad_categories.name')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Ads',
                    'data' => $data->pluck('total')->toArray(),
                    'backgroundColor' => ['#3b82f6', '#ef4444', '#10b981', '#f59e0b', '#6366f1'],
                ],
            ],
            'labels' => $data->pluck('name')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
