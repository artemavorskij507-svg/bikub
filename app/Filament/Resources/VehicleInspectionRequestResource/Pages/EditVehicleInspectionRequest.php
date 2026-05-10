<?php

namespace App\Filament\Resources\VehicleInspectionRequestResource\Pages;

use App\Filament\Resources\VehicleInspectionRequestResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVehicleInspectionRequest extends EditRecord
{
    protected static string $resource = VehicleInspectionRequestResource::class;

    protected function getActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
