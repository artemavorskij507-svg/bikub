<?php

namespace App\Filament\Resources\RoadsideEmergencyResource\Pages;

use App\Filament\Resources\RoadsideEmergencyResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRoadsideEmergency extends EditRecord
{
    protected static string $resource = RoadsideEmergencyResource::class;

    protected function getActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
