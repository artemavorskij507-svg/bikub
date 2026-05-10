<?php

namespace App\Filament\Resources\AnalyticsResource\Pages;

use App\Filament\Resources\AnalyticsResource;
use Filament\Resources\Pages\ListRecords;

class ListAnalytics extends ListRecords
{
    protected static string $resource = AnalyticsResource::class;

    protected function getActions(): array
    {
        return [
            // Removed CreateAction - analytics is read-only
        ];
    }

    protected function canCreate(): bool
    {
        return false; // Explicitly disable creation
    }
}
