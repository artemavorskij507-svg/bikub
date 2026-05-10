<?php

namespace App\Filament\Resources\Moving\MovingOrderResource\Pages;

use App\Filament\Resources\Moving\MovingOrderResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewMovingOrder extends ViewRecord
{
    protected static string $resource = MovingOrderResource::class;

    protected function getActions(): array
    {
        return [Actions\EditAction::make()];
    }
}
