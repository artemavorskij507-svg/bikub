<?php

namespace App\Filament\Resources\ApiKeyResource\Pages;

use App\Filament\Resources\ApiKeyResource;
use App\Models\ApiKey;
use App\Services\ApiKeyService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateApiKey extends CreateRecord
{
    protected static string $resource = ApiKeyResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // API Key generation is handled via service, not in form.
        // Scopes come from form, but we generate the key here.
        return $data;
    }

    protected function handleRecordCreation(array $data): ApiKey
    {
        // Use ApiKeyService to generate the key
        $service = app(ApiKeyService::class);

        $result = $service->generate(
            $data['owner_type'],
            $data['owner_id'] ?? null,
            $data['name'],
            $data['scopes'] ?? [],
            $data['expires_at'] ? $data['expires_at']->diffInDays(now()) : null,
            false
        );

        // Retrieve the created model
        $model = ApiKey::find($result['id']);

        // Store plaintext key in session for one-time display
        session()->flash('new_api_key_plaintext', $result['api_key']);
        session()->flash('new_api_key_id', $result['id']);

        // Log creation via observer (triggered by create)
        return $model;
    }

    protected function getRedirectUrl(): string
    {
        // After successful creation, redirect to list and flash success message
        return $this->getResource()::getUrl('index');
    }

    public function getCreatedNotification(): ?\Filament\Notifications\Notification
    {
        // Instead of default notification, we show the key via a Notification with modal
        $plaintext = session()->get('new_api_key_plaintext');
        if ($plaintext) {
            return \Filament\Notifications\Notification::make()
                ->title('API Key Created')
                ->body('Your new API key: '.substr($plaintext, 0, 20).'... (Copy and save now!)')
                ->warning()
                ->persistent();
        }

        return parent::getCreatedNotification();
    }

    protected function getTitle(): string
    {
        return 'Create API Key';
    }
}
