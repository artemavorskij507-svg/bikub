<?php

namespace App\Filament\Pages\Security;

use App\Filament\Resources\ApiKeyResource;
use Filament\Pages\Page;

class ApiKeys extends Page
{
    protected static string $view = 'filament.security.api-keys';

    protected static ?string $title = 'API Keys Management';

    public function mount(): void
    {
        $this->redirect(ApiKeyResource::getUrl('index'));
    }
}
