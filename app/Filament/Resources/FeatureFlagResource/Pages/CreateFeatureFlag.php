<?php

namespace App\Filament\Resources\FeatureFlagResource\Pages;

use App\Filament\Resources\FeatureFlagResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateFeatureFlag extends CreateRecord
{
    protected static string $resource = FeatureFlagResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Auto-set enabled_by and enabled_at if enabled
        if (! empty($data['enabled'])) {
            $data['enabled_by'] = $data['enabled_by'] ?? Auth::id();
            $data['enabled_at'] = $data['enabled_at'] ?? now();
        }

        return $data;
    }
}
