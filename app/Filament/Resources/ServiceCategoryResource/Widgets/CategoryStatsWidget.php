<?php

namespace App\Filament\Resources\ServiceCategoryResource\Widgets;

use App\Models\ServiceCategory;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Card;

class CategoryStatsWidget extends BaseWidget
{
    protected function getFilters(): ?array
    {
        return null;
    }

    public ?ServiceCategory $record = null;

    public function getRecord(): ?ServiceCategory
    {
        if ($this->record) {
            return $this->record;
        }

        $owner = $this->getOwner();
        if ($owner instanceof \App\Filament\Resources\ServiceCategoryResource\Pages\EditServiceCategory) {
            return $owner->record;
        }

        return null;
    }

    protected function getCards(): array
    {
        $record = $this->getRecord();

        if (! $record) {
            return [];
        }

        $serviceTypesCount = $record->serviceTypes()->count();
        $activeServiceTypesCount = $record->serviceTypes()->where('is_active', true)->count();
        $inactiveServiceTypesCount = $serviceTypesCount - $activeServiceTypesCount;

        $ordersCount = \App\Models\Order::whereHas('orderItems.serviceType', function ($q) use ($record) {
            $q->where('service_category_id', $record->id);
        })->count();

        return [
            Card::make('Total services', $serviceTypesCount)
                ->description('In this category')
                ->color('primary'),
            Card::make('Active services', $activeServiceTypesCount)
                ->description('Available for orders')
                ->color('success'),
            Card::make('Inactive services', $inactiveServiceTypesCount)
                ->description('Hidden from customers')
                ->color('warning'),
            Card::make('Total orders', $ordersCount)
                ->description('Linked to this category')
                ->color('info'),
        ];
    }
}
