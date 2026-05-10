<?php

namespace App\Filament\Resources\CarePlanResource\Pages;

use App\Filament\Resources\CarePlanResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCarePlan extends EditRecord
{
    protected static string $resource = CarePlanResource::class;

    protected function getActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
