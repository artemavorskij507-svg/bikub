<?php

namespace App\Filament\Resources\CareServiceResource\Pages;

use App\Filament\Resources\CareServiceResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCareService extends EditRecord
{
    protected static string $resource = CareServiceResource::class;

    protected function getActions(): array
    {
        return [Actions\ViewAction::make(),            Actions\DeleteAction::make()];
    }
}
