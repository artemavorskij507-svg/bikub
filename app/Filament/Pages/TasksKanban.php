<?php

namespace App\Filament\Pages;

use App\Models\Employee;
use App\Models\Task;
use App\Services\TaskGenerator;
use Filament\Pages\Actions\Action as PageAction;
use Filament\Pages\Page;

class TasksKanban extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-collection';

    protected static ?string $navigationGroup = 'System & Operations';

    protected static ?string $navigationLabel = 'Tasks (Kanban)';

    protected static ?int $navigationSort = 5;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    protected static string $view = 'filament.pages.tasks-kanban';

    public array $columns = [
        'queued', 'ready', 'assigned', 'en_route', 'arrived', 'in_progress', 'paused', 'completed', 'failed', 'canceled', 'rescheduled',
    ];

    public function getHeaderActions(): array
    {
        return [
            PageAction::make('generateDemo')
                ->label('Generate demo tasks')
                ->color('success')
                ->action(function () {
                    // Generate demo for last 3 orders if exist
                    $orders = \App\Models\Order::query()->latest()->limit(3)->get();
                    foreach ($orders as $order) {
                        app(TaskGenerator::class)->generateForOrder($order);
                    }
                }),
        ];
    }

    public function getTasksByStatus(): array
    {
        $data = [];
        foreach ($this->columns as $status) {
            $data[$status] = Task::query()->where('status', $status)->orderByDesc('id')->limit(50)->get();
        }

        return $data;
    }

    public function quickMove(int $taskId, string $to): void
    {
        if (! in_array($to, $this->columns, true)) {
            return;
        }
        $task = Task::find($taskId);
        if (! $task) {
            return;
        }
        $task->status = $to;
        $task->save();
    }

    public function quickAssign(int $taskId, ?int $employeeId): void
    {
        $task = Task::find($taskId);
        if (! $task) {
            return;
        }
        $task->assignee_id = $employeeId;
        $task->save();
    }

    public function getEmployees(): array
    {
        return Employee::query()
            ->selectRaw("id, (COALESCE(first_name,'') || ' ' || COALESCE(last_name,'')) as full_name")
            ->orderBy('full_name')
            ->pluck('full_name', 'id')
            ->all();
    }
}
