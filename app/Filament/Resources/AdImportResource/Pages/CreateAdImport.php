<?php

namespace App\Filament\Resources\AdImportResource\Pages;

use App\Filament\Resources\AdImportResource;
use App\Jobs\ProcessAdImportJob;
use Filament\Resources\Pages\CreateRecord;

class CreateAdImport extends CreateRecord
{
    protected static string $resource = AdImportResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();
        $data['status'] = 'pending';

        return $data;
    }

    protected function afterCreate(): void
    {
        ProcessAdImportJob::dispatch($this->record);
    }
}
