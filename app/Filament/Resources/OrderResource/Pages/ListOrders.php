<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Schema;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    protected function getActions(): array
    {
        return [];
    }

    protected function getTableQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $relations = ['orderItems.serviceType'];

        if (Schema::hasTable('roadside_assistance_details')) {
            $relations[] = 'roadsideDetails';
        }

        return parent::getTableQuery()->with($relations);
    }
}
