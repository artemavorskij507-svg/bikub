<?php

namespace App\Filament\Resources\RetailStoreResource\Pages;

use App\Filament\Resources\RetailStoreResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRetailStore extends EditRecord
{
    protected static string $resource = RetailStoreResource::class;

    protected function getActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
