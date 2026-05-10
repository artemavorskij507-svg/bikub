<?php

namespace App\Filament\Resources\RepairStageResource\Pages;

use App\Filament\Resources\RepairStageResource;
use App\Models\RepairProject;
use App\Models\RepairStage;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class ListRepairStages extends ListRecords
{
    protected static string $resource = RepairStageResource::class;

    public function mount(): void
    {
        parent::mount();

        $this->ensureStagesSchema();
        $this->seedStagesIfEmpty();
    }

    protected function ensureStagesSchema(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        $paths = [
            'database/migrations/2025_11_17_151100_create_repair_projects_table.php',
            'database/migrations/2025_11_17_151200_create_repair_stages_table.php',
        ];

        foreach ($paths as $path) {
            if (! is_file(base_path($path))) {
                continue;
            }

            try {
                Artisan::call('migrate', [
                    '--path' => $path,
                    '--force' => true,
                ]);
            } catch (\Throwable $exception) {
                Log::warning('Repair stages schema bootstrap failed', [
                    'path' => $path,
                    'error' => $exception->getMessage(),
                ]);
            }
        }
    }

    protected function seedStagesIfEmpty(): void
    {
        if (! app()->environment('local') || ! Schema::hasTable('repair_stages') || RepairStage::query()->exists()) {
            return;
        }

        $repairProjectId = Schema::hasTable('repair_projects') ? DB::table('repair_projects')->value('id') : null;
        if (! $repairProjectId) {
            $orderId = Schema::hasTable('orders') ? DB::table('orders')->value('id') : null;
            if (! $orderId) {
                return;
            }
            $repairProjectId = RepairProject::query()->create([
                'order_id' => $orderId,
                'title' => 'Demo: базовый ремонт',
                'status' => 'in_progress',
            ])->id;
        }

        RepairStage::query()->create([
            'repair_project_id' => $repairProjectId,
            'name' => 'Подготовка поверхности',
            'description' => 'Демо-этап для локальной проверки.',
            'sequence' => 1,
            'status' => 'in_progress',
            'planned_start_at' => now()->subHours(6),
            'planned_finish_at' => now()->addDay(),
            'progress_percent' => 35,
        ]);
    }
}
