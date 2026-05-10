<?php

namespace App\Filament\Resources\PayoutResource\Pages;

use App\Filament\Resources\PayoutResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePayout extends CreateRecord
{
    protected static string $resource = PayoutResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // На создании по умолчанию ставим pending
        $data['status'] = $data['status'] ?? 'pending';

        return $data;
    }
}
