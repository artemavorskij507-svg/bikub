<?php

namespace App\Filament\Resources\GeoZoneResource\Pages;

use App\Filament\Resources\GeoZoneResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewGeoZone extends ViewRecord
{
    protected static string $resource = GeoZoneResource::class;

    protected function getActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
