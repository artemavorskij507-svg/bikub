<?php

namespace App\Filament\Resources\FeatureFlagResource\Pages;

use App\Filament\Resources\FeatureFlagResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewFeatureFlag extends ViewRecord
{
    protected static string $resource = FeatureFlagResource::class;

    protected function getActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
