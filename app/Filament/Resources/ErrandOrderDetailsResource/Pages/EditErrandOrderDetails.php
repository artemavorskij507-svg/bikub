<?php

namespace App\Filament\Resources\ErrandOrderDetailsResource\Pages;

use App\Filament\Resources\ErrandOrderDetailsResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditErrandOrderDetails extends EditRecord
{
    protected static string $resource = ErrandOrderDetailsResource::class;

    protected function getActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
