<?php

namespace App\Filament\Resources\RepairProjectResource\Pages;

use App\Filament\Resources\RepairProjectResource;
use App\Models\RepairProject;
use App\Services\Repair\RepairUpdateService;
use Filament\Pages\Actions;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;

class ProjectDashboard extends Page
{
    use InteractsWithRecord;

    protected static string $resource = RepairProjectResource::class;

    protected static string $view = 'filament.resources.repair-project-resource.pages.project-dashboard';

    public function mount($record): void
    {
        $this->record = $this->resolveRecord($record);
        $this->record->load(['order',            'projectManager.user',            'stages' => fn ($query) => $query->orderBy('sequence'),            'updates' => fn ($query) => $query->latest()->limit(10),            'media' => fn ($query) => $query->latest()->limit(12)])->loadCount('media');
    }

    protected function getHeading(): string
    {
        return 'Project Dashboard: '.$this->record->title;
    }

    protected function getActions(): array
    {
        return [Actions\Action::make('add_update')->label('Добавить обновление')->icon('heroicon-o-plus-circle')->form([\Filament\Forms\Components\Select::make('repair_stage_id')->label('Этап')->options($this->record->stages->pluck('name', 'id'))->searchable()->placeholder('Общий прогресс')->nullable(),                    \Filament\Forms\Components\TextInput::make('title')->label('Заголовок')->required(),                    \Filament\Forms\Components\Textarea::make('body')->label('Описание')->rows(3),                    \Filament\Forms\Components\TextInput::make('progress_percent')->label('Прогресс (%)')->numeric()->minValue(0)->maxValue(100),                    \Filament\Forms\Components\Select::make('status_snapshot')->label('Статус проекта')->options(['draft' => 'Черновик',                            'assessment' => 'Оценка',                            'estimating' => 'Смета',                            'scheduled' => 'Запланирован',                            'in_progress' => 'В работе',                            'on_hold' => 'Пауза',                            'completed' => 'Завершён',                            'cancelled' => 'Отменён'])])->action(function (array $data) {                    /** @var RepairProject $project */ $project = $this->record;
            $stage = $project->stages->firstWhere('id', $data['repair_stage_id'] ?? null);
            app(RepairUpdateService::class)->createStatusUpdate($project, $stage, auth()->id(), $data['title'], $data['body'] ?? null, $data['progress_percent'] !== null ? (int) $data['progress_percent'] : null, $data['status_snapshot'] ?? null);
            $this->notify('success', 'Обновление добавлено.');
            $this->record->refresh()->load(['stages' => fn ($query) => $query->orderBy('sequence'),                        'updates' => fn ($query) => $query->latest()->limit(10),                        'media' => fn ($query) => $query->latest()->limit(12)])->loadCount('media');
        })->visible(fn () => auth()->check())];
    }

    protected function getViewData(): array
    {
        return ['project' => $this->record,            'stages' => $this->record->stages,            'updates' => $this->record->updates,            'media' => $this->record->media];
    }
}
