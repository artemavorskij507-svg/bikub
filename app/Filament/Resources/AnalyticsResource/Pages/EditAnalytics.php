<?php

namespace App\Filament\Resources\AnalyticsResource\Pages;

use App\Filament\Resources\AnalyticsResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAnalytics extends EditRecord
{
    protected static string $resource = AnalyticsResource::class;

    protected function getActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
