<?php

namespace App\Filament\Resources\ExecutorShiftResource\Pages;

use App\Domain\Dispatch\Models\ExecutorShift;
use App\Domain\Ops\Actions\RecordDispatchConfigAuditAction;
use App\Filament\Resources\ExecutorShiftResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateExecutorShift extends CreateRecord
{
    protected static string $resource = ExecutorShiftResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();
        $data['organization_id'] = $data['organization_id'] ?? (string) ($user->organization_id ?? $user->default_org_id ?? '');
        $data['tenant_id'] = $data['tenant_id'] ?? (string) ($user->tenant_id ?? '');

        $exists = ExecutorShift::query()
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

    protected function afterCreate(): void
    {
        app(RecordDispatchConfigAuditAction::class)->execute(auth()->id(), 'executor_shift_created', 'executor_shift', $this->record->id, [], $this->record->toArray());
    }
}
