<?php

namespace App\Filament\Resources\ClientProfileResource\Pages;

use App\Filament\Resources\ClientProfileResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditClientProfile extends EditRecord
{
    protected static string $resource = ClientProfileResource::class;

    protected function getActions(): array
    {
        return [Actions\ViewAction::make(),            Actions\DeleteAction::make()];
    }
}
