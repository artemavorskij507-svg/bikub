<?php

namespace App\Filament\Resources\CareServiceResource\Pages;

use App\Filament\Resources\CareServiceResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewCareService extends ViewRecord
{
    protected static string $resource = CareServiceResource::class;

    protected function getActions(): array
    {
        return [Actions\EditAction::make()];
    }
}
