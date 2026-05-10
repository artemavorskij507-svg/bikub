<?php

namespace App\Filament\Resources\ServiceTypeResource\Pages;

use App\Filament\Resources\ServiceTypeResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditServiceType extends EditRecord
{
    protected static string $resource = ServiceTypeResource::class;

    protected function getActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
