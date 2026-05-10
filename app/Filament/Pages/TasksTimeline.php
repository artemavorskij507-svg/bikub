<?php

namespace App\Filament\Pages;

use App\Models\Task;
use Filament\Pages\Page;

class TasksTimeline extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    protected static ?string $navigationGroup = 'System & Operations';

    protected static ?string $navigationLabel = 'Tasks (Timeline)';

    protected static ?int $navigationSort = 6;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    protected static string $view = 'filament.pages.tasks-timeline';

    public array $rows = [];

    public ?string $date = null;

    public function mount(): void
    {
        $this->date = now()->toDateString();
        $this->loadData();
    }

    public function updatedDate(): void
    {
        $this->loadData();
    }

    protected function loadData(): void
    {
        try {
            $start = $this->date ? \Carbon\Carbon::parse($this->date)->startOfDay() : now()->startOfDay();
        } catch (\Exception $e) {
            \Log::warning('Failed to parse date in TasksTimeline', [
                'date' => $this->date,
                'error' => $e->getMessage(),
            ]);
            $start = now()->startOfDay();
        }
        $end = (clone $start)->endOfDay();

        $this->rows = Task::query()
            ->with(['zone'])
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('window_start', [$start, $end])
                    ->orWhereBetween('window_end', [$start, $end]);
            })
            ->orderBy('zone_id')
            ->orderBy('window_start')
            ->get()
            ->groupBy(fn ($t) => optional($t->zone)->name ?: 'No zone')
            ->toArray();
    }
}
