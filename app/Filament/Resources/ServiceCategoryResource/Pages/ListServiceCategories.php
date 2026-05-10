<?php

namespace App\Filament\Resources\ServiceCategoryResource\Pages;

use App\Filament\Resources\ServiceCategoryResource;
use App\Models\ServiceCategory;
use App\Models\ServiceType;
use Filament\Pages\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Schema;

class ListServiceCategories extends ListRecords
{
    protected static string $resource = ServiceCategoryResource::class;

    public function mount(): void
    {
        parent::mount();

        $this->seedLocalDemoServiceCategoriesIfEmpty();
    }

    protected function getActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ServiceCategoryResource\Widgets\CategoriesOverviewWidget::class,
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All')
                ->badge(ServiceCategory::count()),
            'active' => Tab::make('Active')
                ->badge(ServiceCategory::where('is_active', true)->count())
                ->modifyQueryUsing(fn ($query) => $query->where('is_active', true)),
            'inactive' => Tab::make('Inactive')
                ->badge(ServiceCategory::where('is_active', false)->count())
                ->modifyQueryUsing(fn ($query) => $query->where('is_active', false)),
            'with_services' => Tab::make('With services')
                ->badge(ServiceCategory::whereHas('serviceTypes')->count())
                ->modifyQueryUsing(fn ($query) => $query->whereHas('serviceTypes')),
            'without_services' => Tab::make('Without services')
                ->badge(ServiceCategory::whereDoesntHave('serviceTypes')->count())
                ->modifyQueryUsing(fn ($query) => $query->whereDoesntHave('serviceTypes')),
        ];
    }

    protected function seedLocalDemoServiceCategoriesIfEmpty(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        if (! Schema::hasTable('service_categories')) {
            return;
        }

        if (ServiceCategory::query()->exists()) {
            return;
        }

        $careCategory = ServiceCategory::query()->create([
            'code' => 'care',
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
        ]);

        $deliveryCategory = ServiceCategory::query()->create([
            'code' => 'delivery',
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
        ]);

        if (Schema::hasTable('service_types') && ! ServiceType::query()->exists()) {
            ServiceType::query()->create([
                'name' => 'Home Care Basic',
                'slug' => 'home-care-basic',
                'category' => 'care',
                'description' => 'Basic home care assistance',
                'service_category_id' => $careCategory->id,
                'is_active' => true,
                'sort_order' => 10,
            ]);

            ServiceType::query()->create([
                'name' => 'Courier Express',
                'slug' => 'courier-express',
                'category' => 'delivery',
                'description' => 'Fast same-day delivery',
                'service_category_id' => $deliveryCategory->id,
                'is_active' => true,
                'sort_order' => 20,
            ]);
        }
    }
}
