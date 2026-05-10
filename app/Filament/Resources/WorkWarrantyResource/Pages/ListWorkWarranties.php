<?php

namespace App\Filament\Resources\WorkWarrantyResource\Pages;

use App\Filament\Resources\WorkWarrantyResource;
use App\Models\RepairProject;
use App\Models\WorkWarranty;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class ListWorkWarranties extends ListRecords
{
    protected static string $resource = WorkWarrantyResource::class;

    public function mount(): void
    {
        parent::mount();

        $this->ensureWarrantySchema();
        $this->seedWarrantiesIfEmpty();
    }

    protected function ensureWarrantySchema(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        $paths = [
            'database/migrations/2025_11_17_151100_create_repair_projects_table.php',
            'database/migrations/2025_11_17_151500_create_work_warranties_table.php',
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
                Log::warning('Work warranties schema bootstrap failed', [
                    'path' => $path,
                    'error' => $exception->getMessage(),
                ]);
            }
        }
    }

    protected function seedWarrantiesIfEmpty(): void
    {
        if (! app()->environment('local') || ! Schema::hasTable('work_warranties') || WorkWarranty::query()->exists()) {
            return;
        }

        $orderId = Schema::hasTable('orders') ? DB::table('orders')->value('id') : null;
        $repairProjectId = Schema::hasTable('repair_projects') ? DB::table('repair_projects')->value('id') : null;

        if (! $orderId && ! $repairProjectId) {
            return;
        }

        if (! $repairProjectId && $orderId) {
            $repairProjectId = RepairProject::query()->create([
                'order_id' => $orderId,
                'title' => 'Demo: проект под гарантию',
                'status' => 'completed',
            ])->id;
        }

        WorkWarranty::query()->create([
            'order_id' => $orderId,
            'repair_project_id' => $repairProjectId,
            'title' => 'Гарантия на выполненные работы',
            'description' => 'Демо-гарантия для локального стенда.',
            'starts_at' => now()->subDays(2),
            'ends_at' => now()->addMonths(6),
            'status' => 'active',
            'terms_url' => 'https://example.com/warranty-terms',
        ]);
    }
}
