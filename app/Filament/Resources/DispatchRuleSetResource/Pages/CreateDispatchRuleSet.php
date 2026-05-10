<?php

namespace App\Filament\Resources\DispatchRuleSetResource\Pages;

use App\Filament\Resources\DispatchRuleSetResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDispatchRuleSet extends CreateRecord
{
    protected static string $resource = DispatchRuleSetResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        try {
            $data = DispatchRuleSetResource::mutateBeforeSave($data);
        } catch (\Throwable $e) {
            DispatchRuleSetResource::reportValidationError($e);
        }

        $user = auth()->user();
        $data['organization_id'] = $data['organization_id'] ?? (string) ($user->organization_id ?? $user->default_org_id ?? '');
        $data['tenant_id'] = $data['tenant_id'] ?? (string) ($user->tenant_id ?? '');

        return $data;
    }

    protected function afterCreate(): void
    {
        DispatchRuleSetResource::audit('dispatch_rule_created', $this->record, []);
    }
}
