<?php

namespace App\Filament\Resources\ApiKeyResource\Pages;

use App\Filament\Resources\ApiKeyResource;
use App\Models\ApiKey;
use Filament\Pages\Page;

class ShowApiKey extends Page
{
    protected static string $resource = ApiKeyResource::class;

    protected static string $view = 'filament.resources.api-key-resource.show-api-key';

    public ?ApiKey $record = null;

    public ?string $plaintext_key = null;

    public function mount(ApiKey $record): void
    {
        $this->record = $record;

        // Retrieve plaintext key from session (only shown once)
        $this->plaintext_key = session()->pull('new_api_key_plaintext');

        if (! $this->plaintext_key) {
            $this->redirect(ApiKeyResource::getUrl('index'));
        }

        static::authorizeResourceAccess();
    }

    protected function getTitle(): string
    {
        return 'Your New API Key';
    }

    public function getHeading(): string
    {
        return 'API Key Created Successfully';
    }
}
