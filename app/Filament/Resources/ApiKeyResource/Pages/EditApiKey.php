<?php

namespace App\Filament\Resources\ApiKeyResource\Pages;

use App\Filament\Resources\ApiKeyResource;
use Filament\Resources\Pages\EditRecord;

class EditApiKey extends EditRecord
{
    protected static string $resource = ApiKeyResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Only name and expires_at can be edited. Scopes require rotation.
        // This is enforced in the form via disabled() but we double-check here.
        $record = $this->record;

        // Prevent scope changes without rotation
        if ($data['scopes'] !== $record->scopes) {
            throw new \Exception('Cannot modify scopes without rotation. Use the Rotate action instead.');
        }

        return $data;
    }

    protected function getTitle(): string
    {
        return 'Edit API Key';
    }
}
