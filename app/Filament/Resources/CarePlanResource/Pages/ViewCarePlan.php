<?php

namespace App\Filament\Resources\CarePlanResource\Pages;

use App\Filament\Resources\CarePlanResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewCarePlan extends ViewRecord
{
    protected static string $resource = CarePlanResource::class;

    protected function getActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
