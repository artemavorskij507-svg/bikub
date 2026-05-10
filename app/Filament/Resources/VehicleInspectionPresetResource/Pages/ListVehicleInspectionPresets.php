<?php

namespace App\Filament\Resources\VehicleInspectionPresetResource\Pages;

use App\Filament\Resources\VehicleInspectionPresetResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVehicleInspectionPresets extends ListRecords
{
    protected static string $resource = VehicleInspectionPresetResource::class;

    protected function getActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
