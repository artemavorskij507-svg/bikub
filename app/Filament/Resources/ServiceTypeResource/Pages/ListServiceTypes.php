<?php

namespace App\Filament\Resources\ServiceTypeResource\Pages;

use App\Filament\Resources\ServiceTypeResource;
use App\Models\ServiceCategory;
use App\Models\ServiceType;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Schema;

class ListServiceTypes extends ListRecords
{
    protected static string $resource = ServiceTypeResource::class;

    public function mount(): void
    {
        parent::mount();

        $this->seedLocalDemoServiceDataIfEmpty();
    }

    protected function getActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function seedLocalDemoServiceDataIfEmpty(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        if (! Schema::hasTable('service_categories') || ! Schema::hasTable('service_types')) {
            return;
        }

        if (ServiceType::query()->exists()) {
            return;
        }

        $careCategory = ServiceCategory::query()->firstOrCreate(
            ['code' => 'care'],
            [
                'slug' => 'care-services',
                'name' => 'Care Services',
                'short_description' => 'Home and personal care',
                'description' => 'Home and personal care services',
                'icon' => 'heroicon-o-heart',
                'color' => '#22c55e',
                'is_active' => true,
                'show_on_homepage' => true,
                'homepage_order' => 10,
                'order_column' => 10,
                'sort_order' => 10,
            ]
        );

        $deliveryCategory = ServiceCategory::query()->firstOrCreate(
            ['code' => 'delivery'],
            [
                'slug' => 'delivery-services',
                'name' => 'Delivery',
                'short_description' => 'Courier and delivery',
                'description' => 'Courier and delivery services',
                'icon' => 'heroicon-o-truck',
                'color' => '#3b82f6',
                'is_active' => true,
                'show_on_homepage' => true,
                'homepage_order' => 20,
                'order_column' => 20,
                'sort_order' => 20,
            ]
        );

        ServiceType::query()->firstOrCreate(
            ['slug' => 'home-care-basic'],
            [
                'name' => 'Home Care Basic',
                'category' => 'care',
                'description' => 'Basic home care assistance',
                'service_category_id' => $careCategory->id,
                'is_active' => true,
                'sort_order' => 10,
            ]
        );

        ServiceType::query()->firstOrCreate(
            ['slug' => 'courier-express'],
            [
                'name' => 'Courier Express',
                'category' => 'delivery',
                'description' => 'Fast same-day delivery',
                'service_category_id' => $deliveryCategory->id,
                'is_active' => true,
                'sort_order' => 20,
            ]
        );
    }
}
