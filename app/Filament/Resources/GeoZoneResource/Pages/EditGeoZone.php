<?php

namespace App\Filament\Resources\GeoZoneResource\Pages;

use App\Filament\Resources\GeoZoneResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGeoZone extends EditRecord
{
    protected static string $resource = GeoZoneResource::class;

    protected function getActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
