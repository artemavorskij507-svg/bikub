<?php

namespace App\Filament\Resources\AdminIpRuleResource\Pages;

use App\Filament\Resources\AdminIpRuleResource;
use App\Models\AdminIpRule;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateAdminIpRule extends CreateRecord
{
    protected static string $resource = AdminIpRuleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // If creating an allow rule and there are no other active allow rules,
        // require explicit confirmation (confirm_lockdown checkbox)
        if (($data['type'] ?? null) === 'allow') {
            $exists = AdminIpRule::where('type', 'allow')->where('is_active', true)->exists();
            if (! $exists && empty($data['confirm_lockdown'])) {
                throw ValidationException::withMessages([
                    'confirm_lockdown' => ['You must confirm that creating the first active allow rule will restrict admin access to this IP.'],
                ]);
            }
        }

        // Remove the confirm_lockdown field from stored data
        if (array_key_exists('confirm_lockdown', $data)) {
            unset($data['confirm_lockdown']);
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
