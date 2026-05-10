<?php

namespace App\Filament\Resources\LoyaltyTransactionResource\Pages;

use App\Filament\Resources\LoyaltyTransactionResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLoyaltyTransaction extends EditRecord
{
    protected static string $resource = LoyaltyTransactionResource::class;

    protected function getActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
