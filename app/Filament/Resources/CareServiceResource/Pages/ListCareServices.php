<?php

namespace App\Filament\Resources\CareServiceResource\Pages;

use App\Filament\Resources\CareServiceResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCareServices extends ListRecords
{
    protected static string $resource = CareServiceResource::class;

    protected function getActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
