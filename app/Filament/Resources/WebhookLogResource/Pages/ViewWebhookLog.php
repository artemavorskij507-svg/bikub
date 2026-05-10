<?php

namespace App\Filament\Resources\WebhookLogResource\Pages;

use App\Filament\Resources\WebhookLogResource;
use Filament\Resources\Pages\ViewRecord;

class ViewWebhookLog extends ViewRecord
{
    protected static string $resource = WebhookLogResource::class;

    protected function getTitle(): string
    {
        return 'Webhook Details';
    }
}
