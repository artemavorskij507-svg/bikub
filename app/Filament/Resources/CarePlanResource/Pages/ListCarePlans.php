<?php

namespace App\Filament\Resources\CarePlanResource\Pages;

use App\Filament\Resources\CarePlanResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCarePlans extends ListRecords
{
    protected static string $resource = CarePlanResource::class;

    protected function getActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
