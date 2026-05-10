<?php

namespace App\Filament\Resources\DeliveryOrderResource\Pages;

use App\Filament\Resources\DeliveryOrderResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDeliveryOrder extends EditRecord
{
    protected static string $resource = DeliveryOrderResource::class;

    protected function getActions(): array
    {
        return [Actions\ViewAction::make(),            Actions\DeleteAction::make()];
    }
}
