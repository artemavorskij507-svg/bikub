<?php

namespace App\Filament\Resources\RoadsidePresetResource\Pages;

use App\Filament\Resources\RoadsidePresetResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRoadsidePreset extends EditRecord
{
    protected static string $resource = RoadsidePresetResource::class;

    protected function getActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
