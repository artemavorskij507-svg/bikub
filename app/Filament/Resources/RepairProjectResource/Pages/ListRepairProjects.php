<?php

namespace App\Filament\Resources\RepairProjectResource\Pages;

use App\Filament\Resources\RepairProjectResource;
use App\Models\RepairProject;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class ListRepairProjects extends ListRecords
{
    protected static string $resource = RepairProjectResource::class;

    public function mount(): void
    {
        parent::mount();

        $this->ensureRepairSchema();
        $this->seedRepairProjectsIfEmpty();
    }

    protected function ensureRepairSchema(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        $paths = [
            'database/migrations/2025_11_17_151100_create_repair_projects_table.php',
            'database/migrations/2025_11_18_090125_add_overall_progress_to_repair_projects_table.php',
            'database/migrations/2025_11_20_140135_add_handyman_fields_to_repair_projects_table.php',
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
                Log::warning('Repair projects schema bootstrap failed', [
                    'path' => $path,
                    'error' => $exception->getMessage(),
                ]);
            }
        }
    }

    protected function seedRepairProjectsIfEmpty(): void
    {
        if (! app()->environment('local') || ! Schema::hasTable('repair_projects') || RepairProject::query()->exists()) {
            return;
        }

        $orderId = Schema::hasTable('orders') ? DB::table('orders')->value('id') : null;
        if (! $orderId) {
            return;
        }

        $clientProfileId = Schema::hasTable('client_profiles')
            ? DB::table('client_profiles')->where('user_id', DB::table('orders')->where('id', $orderId)->value('user_id'))->value('id')
            : null;

        $managerId = Schema::hasTable('executor_profiles') ? DB::table('executor_profiles')->value('id') : null;

        RepairProject::query()->create([
            'order_id' => $orderId,
            'client_profile_id' => $clientProfileId,
            'project_manager_id' => $managerId,
            'title' => 'Demo: ремонт кухни',
            'description' => 'Локальный демо-проект для проверки интерфейса.',
            'status' => 'in_progress',
            'city' => 'Oslo',
            'planned_start_at' => now()->subDay(),
            'planned_finish_at' => now()->addDays(6),
            'budget_estimate_minor' => 250000,
            'budget_actual_minor' => 120000,
            'overall_progress_percent' => 45,
            'base_price' => 2500,
            'estimated_time' => '6 days',
            'region' => 'Oslo',
        ]);
    }
}
