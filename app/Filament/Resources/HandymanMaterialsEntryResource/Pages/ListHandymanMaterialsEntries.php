<?php

namespace App\Filament\Resources\HandymanMaterialsEntryResource\Pages;

use App\Filament\Resources\HandymanMaterialsEntryResource;
use App\Models\HandymanMaterialsEntry;
use App\Models\RepairProject;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class ListHandymanMaterialsEntries extends ListRecords
{
    protected static string $resource = HandymanMaterialsEntryResource::class;

    public function mount(): void
    {
        parent::mount();

        $this->ensureMaterialsSchema();
        $this->seedMaterialsIfEmpty();
    }

    protected function ensureMaterialsSchema(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        $paths = [
            'database/migrations/2025_11_13_020853_create_executor_profiles_table.php',
            'database/migrations/2025_11_17_151100_create_repair_projects_table.php',
            'database/migrations/2025_11_17_151400_create_handyman_materials_entries_table.php',
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
                Log::warning('Handyman materials schema bootstrap failed', [
                    'path' => $path,
                    'error' => $exception->getMessage(),
                ]);
            }
        }
    }

    protected function seedMaterialsIfEmpty(): void
    {
        if (! app()->environment('local') || ! Schema::hasTable('handyman_materials_entries') || HandymanMaterialsEntry::query()->exists()) {
            return;
        }

        $executorProfileId = $this->resolveExecutorProfileId();
        if (! $executorProfileId) {
            return;
        }

        $orderId = Schema::hasTable('orders') ? DB::table('orders')->value('id') : null;
        $repairProjectId = Schema::hasTable('repair_projects') ? DB::table('repair_projects')->value('id') : null;

        if (! $repairProjectId && $orderId) {
            $repairProjectId = RepairProject::query()->create([
                'order_id' => $orderId,
                'title' => 'Demo: проект материалов',
                'status' => 'in_progress',
            ])->id;
        }

        HandymanMaterialsEntry::query()->create([
            'order_id' => $orderId,
            'repair_project_id' => $repairProjectId,
            'executor_profile_id' => $executorProfileId,
            'description' => 'Грунтовка универсальная',
            'quantity' => 2,
            'unit' => 'pcs',
            'unit_price_minor' => 7900,
            'total_price_minor' => 15800,
            'purchased_at' => now()->subHours(3),
            'receipt_url' => 'https://example.com/receipt/demo-material.pdf',
            'meta' => ['source' => 'local_demo_seed'],
        ]);
    }

    protected function resolveExecutorProfileId(): ?int
    {
        if (! Schema::hasTable('executor_profiles')) {
            return null;
        }

        $id = DB::table('executor_profiles')->value('id');
        if ($id) {
            return (int) $id;
        }

        if (! Schema::hasTable('users')) {
            return null;
        }

        $userId = DB::table('users')->value('id');
        if (! $userId) {
            return null;
        }

        DB::table('executor_profiles')->insert([
            'user_id' => $userId,
            'vehicle_type' => 'truck',
            'rating' => 4.4,
            'completed_orders_count' => 9,
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return (int) DB::table('executor_profiles')->where('user_id', $userId)->value('id');
    }
}
