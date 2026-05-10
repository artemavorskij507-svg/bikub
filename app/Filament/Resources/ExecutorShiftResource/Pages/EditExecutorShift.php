<?php

namespace App\Filament\Resources\ExecutorShiftResource\Pages;

use App\Domain\Dispatch\Models\ExecutorShift;
use App\Domain\Ops\Actions\RecordDispatchConfigAuditAction;
use App\Filament\Resources\ExecutorShiftResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditExecutorShift extends EditRecord
{
    protected static string $resource = ExecutorShiftResource::class;
    protected array $before = [];

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->before = $this->record->toArray();

        $exists = ExecutorShift::query()
            ->where('id', '!=', $this->record->id)
            ->where('executor_id', $data['executor_id'])
            ->where('day_of_week', $data['day_of_week'])
            ->where('start_time', $data['start_time'])
            ->where('end_time', $data['end_time'])
            ->where('is_active', true)
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages(['start_time' => 'Active shift with the same interval already exists for this executor/day.']);
        }

        return $data;
    }

    protected function afterSave(): void
    {
        app(RecordDispatchConfigAuditAction::class)->execute(auth()->id(), 'executor_shift_updated', 'executor_shift', $this->record->id, $this->before, $this->record->fresh()->toArray());
    }
}
