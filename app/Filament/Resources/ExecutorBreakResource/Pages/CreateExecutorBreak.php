<?php

namespace App\Filament\Resources\ExecutorBreakResource\Pages;

use App\Domain\Dispatch\Models\ExecutorBreak;
use App\Domain\Ops\Actions\RecordDispatchConfigAuditAction;
use App\Filament\Resources\ExecutorBreakResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateExecutorBreak extends CreateRecord
{
    protected static string $resource = ExecutorBreakResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();
        $data['organization_id'] = $data['organization_id'] ?? (string) ($user->organization_id ?? $user->default_org_id ?? '');
        $data['tenant_id'] = $data['tenant_id'] ?? (string) ($user->tenant_id ?? '');

        $overlap = ExecutorBreak::query()
            ->where('executor_id', $data['executor_id'])
            ->where('shift_date', $data['shift_date'])
            ->where(function ($q) use ($data): void {
                $q->whereBetween('break_start_at', [$data['break_start_at'], $data['break_end_at']])
                    ->orWhereBetween('break_end_at', [$data['break_start_at'], $data['break_end_at']])
                    ->orWhere(function ($qq) use ($data): void {
                        $qq->where('break_start_at', '<=', $data['break_start_at'])
                            ->where('break_end_at', '>=', $data['break_end_at']);
                    });
            })
            ->exists();

        if ($overlap) {
            throw ValidationException::withMessages(['break_start_at' => 'Break overlaps with an existing break for this executor/date.']);
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        app(RecordDispatchConfigAuditAction::class)->execute(auth()->id(), 'executor_break_created', 'executor_break', $this->record->id, [], $this->record->toArray());
    }
}
