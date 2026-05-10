<?php

namespace App\Filament\Resources\RepairProjectResource\Pages;

use App\Events\RepairProjectStatusUpdated;
use App\Filament\Resources\RepairProjectResource;
use App\Models\RepairProject;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRepairProject extends EditRecord
{
    protected static string $resource = RepairProjectResource::class;

    protected function getActions(): array
    {
        return [Actions\Action::make('generate_default_stages')->label('Сгенерировать стандартные этапы')->icon('heroicon-o-sparkles')->color('primary')->requiresConfirmation()->modalHeading('Сгенерировать стандартные этапы?')->modalSubheading('Это создаст 5 стандартных этапов проекта. Если этапы уже существуют, они не будут удалены.')->action(function (?RepairProject $record) {
            if (! $record) {
                $this->notify('danger', 'Проект не найден.');

                return;
            }                    $controller = new \App\Http\Controllers\Public\Repair\RepairIntakeController(app(\App\Services\Notifications\NotificationFeedService::class));
            $controller->createDefaultStagesForProject($record);
            $this->notify('success', 'Стандартные этапы созданы');
        })->visible(fn (?RepairProject $record) => $record && $record->stages()->count() === 0),            Actions\Action::make('start_project')->label('Начать проект')->icon('heroicon-o-play')->color('success')->requiresConfirmation()->modalHeading('Начать проект?')->modalSubheading('Проект будет переведен в статус "В работе" и будет установлена фактическая дата начала.')->action(function (?RepairProject $record) {
            if (! $record) {
                $this->notify('danger', 'Проект не найден.');

                return;
            }                    $oldStatus = $record->status;
            $record->update(['status' => 'in_progress',                        'actual_start_at' => $record->actual_start_at ?? now()]);
            event(new RepairProjectStatusUpdated($record, $oldStatus, $record->status));
            $this->notify('success', 'Проект начат');
        })->visible(fn (?RepairProject $record) => $record && in_array($record->status, ['assessment', 'estimating', 'scheduled'], true)),            Actions\Action::make('complete_project')->label('Завершить проект')->icon('heroicon-o-check-circle')->color('success')->requiresConfirmation()->modalHeading('Завершить проект?')->modalSubheading(function (?RepairProject $record) {
            if (! $record) {
                return 'Проект еще не загружен.';
            }                    $incompleteStages = $record->stages()->where('status', '!=', 'completed')->count();
            if ($incompleteStages > 0) {
                return "Внимание: {$incompleteStages} этапов еще не завершены. Проект все равно будет завершен.";
            }

return 'Проект будет переведен в статус "Завершен" и будет установлена фактическая дата окончания.';
        })->action(function (?RepairProject $record) {
            if (! $record) {
                $this->notify('danger', 'Проект не найден.');

                return;
            }                    $oldStatus = $record->status;
            $record->update(['status' => 'completed',                        'actual_finish_at' => now()]);
            event(new RepairProjectStatusUpdated($record, $oldStatus, $record->status));
            $this->notify('success', 'Проект завершен');
        })->visible(fn (?RepairProject $record) => $record && $record->status !== 'completed' && $record->status !== 'cancelled'),            ...parent::getActions()];
    }
}
