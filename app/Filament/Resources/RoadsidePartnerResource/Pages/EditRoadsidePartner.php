<?php

namespace App\Filament\Resources\RoadsidePartnerResource\Pages;

use App\Filament\Resources\RoadsidePartnerResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRoadsidePartner extends EditRecord
{
    protected static string $resource = RoadsidePartnerResource::class;

    protected function getActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
