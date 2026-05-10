<?php

namespace App\Filament\Resources\DispatchRuleSetResource\Pages;

use App\Filament\Resources\DispatchRuleSetResource;
use Filament\Resources\Pages\EditRecord;

class EditDispatchRuleSet extends EditRecord
{
    protected static string $resource = DispatchRuleSetResource::class;
    protected array $before = [];

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->before = $this->record->toArray();
        try {
            return DispatchRuleSetResource::mutateBeforeSave($data);
        } catch (\Throwable $e) {
            DispatchRuleSetResource::reportValidationError($e);
        }
    }

    protected function afterSave(): void
    {
        DispatchRuleSetResource::audit('dispatch_rule_updated', $this->record?->fresh(), $this->before);
    }
}
