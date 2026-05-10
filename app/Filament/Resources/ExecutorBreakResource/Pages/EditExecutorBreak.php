<?php

namespace App\Filament\Resources\ExecutorBreakResource\Pages;

use App\Domain\Dispatch\Models\ExecutorBreak;
use App\Domain\Ops\Actions\RecordDispatchConfigAuditAction;
use App\Filament\Resources\ExecutorBreakResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditExecutorBreak extends EditRecord
{
    protected static string $resource = ExecutorBreakResource::class;
    protected array $before = [];

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->before = $this->record->toArray();

        $overlap = ExecutorBreak::query()
            ->where('id', '!=', $this->record->id)
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

    protected function afterSave(): void
    {
        app(RecordDispatchConfigAuditAction::class)->execute(auth()->id(), 'executor_break_updated', 'executor_break', $this->record->id, $this->before, $this->record->fresh()->toArray());
    }
}
