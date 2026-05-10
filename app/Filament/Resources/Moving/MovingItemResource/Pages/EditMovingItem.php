<?php

namespace App\Filament\Resources\Moving\MovingItemResource\Pages;

use App\Filament\Resources\Moving\MovingItemResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMovingItem extends EditRecord
{
    protected static string $resource = MovingItemResource::class;

    protected function getActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
