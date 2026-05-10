<?php

namespace App\Filament\Resources\ServiceCategoryResource\Widgets;

use App\Models\ServiceCategory;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Card;

class CategoriesOverviewWidget extends BaseWidget
{
    protected function getCards(): array
    {
        $totalCategories = ServiceCategory::count();
        $activeCategories = ServiceCategory::where('is_active', true)->count();
        $categoriesWithServices = ServiceCategory::whereHas('serviceTypes')->count();
        $totalServices = \App\Models\ServiceType::count();

        return [
            Card::make('Total categories', $totalCategories)
                ->description('In the system')
                ->color('primary'),
            Card::make('Active categories', $activeCategories)
                ->description('Available for use')
                ->color('success'),
            Card::make('With services', $categoriesWithServices)
                ->description('Have linked services')
                ->color('info'),
            Card::make('Total services', $totalServices)
                ->description('Across all categories')
                ->color('warning'),
        ];
    }
}
