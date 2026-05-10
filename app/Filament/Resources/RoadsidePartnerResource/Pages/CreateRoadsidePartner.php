<?php

namespace App\Filament\Resources\RoadsidePartnerResource\Pages;

use App\Filament\Resources\RoadsidePartnerResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRoadsidePartner extends CreateRecord
{
    protected static string $resource = RoadsidePartnerResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['type'] = \App\Models\Partner::TYPE_TOWING_SERVICE;

        return $data;
    }
}
