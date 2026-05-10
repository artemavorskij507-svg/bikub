<?php

namespace App\Filament\Resources\AdminIpRuleResource\Pages;

use App\Filament\Resources\AdminIpRuleResource;
use App\Models\AdminIpRule;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditAdminIpRule extends EditRecord
{
    protected static string $resource = AdminIpRuleResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // If deactivating an allow rule, ensure it's not the last active allow
        $record = $this->record;
        if (($record->type ?? null) === 'allow') {
            $newIsActive = $data['is_active'] ?? $record->is_active;
            if (! $newIsActive) {
                $count = AdminIpRule::where('type', 'allow')->where('is_active', true)->where('id', '!=', $record->id)->count();
                if ($count === 0) {
                    throw ValidationException::withMessages([
                        'is_active' => ['Cannot deactivate the last active allow rule. At least one active allow rule must exist.'],
                    ]);
                }
            }
        }

        return $data;
    }

    protected function beforeDelete(): void
    {
        $record = $this->record;
        if ($record->type === 'allow' && $record->is_active) {
            $count = AdminIpRule::where('type', 'allow')->where('is_active', true)->where('id', '!=', $record->id)->count();
            if ($count === 0) {
                throw ValidationException::withMessages([
                    'delete' => ['Cannot delete the last active allow rule. At least one active allow rule must exist.'],
                ]);
            }
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
