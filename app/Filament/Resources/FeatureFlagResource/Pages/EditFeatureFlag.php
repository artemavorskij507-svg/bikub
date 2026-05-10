<?php

namespace App\Filament\Resources\FeatureFlagResource\Pages;

use App\Filament\Resources\FeatureFlagResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditFeatureFlag extends EditRecord
{
    protected static string $resource = FeatureFlagResource::class;

    protected function getActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Track changes to enabled status
        $wasEnabled = $this->record->enabled;
        $isEnabled = ! empty($data['enabled']);

        // If enabled state changed, update enabled_by and enabled_at
        if ($wasEnabled != $isEnabled) {
            if ($isEnabled) {
                $data['enabled_by'] = Auth::id();
                $data['enabled_at'] = now();
            } else {
                // Keep enabled_by and enabled_at for history
                // Optionally clear them: $data['enabled_by'] = null; $data['enabled_at'] = null;
            }
        }

        return $data;
    }
}
