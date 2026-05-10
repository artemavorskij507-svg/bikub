<?php

namespace App\Filament\Resources\PriceEstimateLogResource\Pages;

use App\Filament\Resources\PriceEstimateLogResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPriceEstimateLog extends EditRecord
{
    protected static string $resource = PriceEstimateLogResource::class;

    protected function getActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
