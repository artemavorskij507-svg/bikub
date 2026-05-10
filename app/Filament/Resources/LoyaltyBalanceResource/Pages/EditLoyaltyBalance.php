<?php

namespace App\Filament\Resources\LoyaltyBalanceResource\Pages;

use App\Filament\Resources\LoyaltyBalanceResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLoyaltyBalance extends EditRecord
{
    protected static string $resource = LoyaltyBalanceResource::class;

    protected function getActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
