<?php

namespace App\Filament\Resources\ClientProfileResource\Pages;

use App\Filament\Resources\ClientProfileResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewClientProfile extends ViewRecord
{
    protected static string $resource = ClientProfileResource::class;

    protected function getActions(): array
    {
        return [Actions\EditAction::make()];
    }
}
