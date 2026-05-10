<?php

namespace App\Filament\Resources\HandymanAssignmentResource\Pages;

use App\Filament\Resources\HandymanAssignmentResource;
use App\Models\HandymanAssignment;
use App\Models\RepairProject;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class ListHandymanAssignments extends ListRecords
{
    protected static string $resource = HandymanAssignmentResource::class;

    public function mount(): void
    {
        parent::mount();

        $this->ensureAssignmentsSchema();
        $this->seedAssignmentsIfEmpty();
    }

    protected function getActions(): array
    {
        return [Actions\CreateAction::make()];
    }

    protected function ensureAssignmentsSchema(): void
    {
        if (! $this->isLocalRuntime()) {
            return;
        }

        $paths = [
            'database/migrations/2025_11_13_020853_create_executor_profiles_table.php',
            'database/migrations/2025_11_17_151100_create_repair_projects_table.php',
            'database/migrations/2025_11_17_192958_create_handyman_assignments_table.php',
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
                Log::warning('Handyman assignments schema bootstrap failed', [
                    'path' => $path,
                    'error' => $exception->getMessage(),
                ]);
            }
        }
    }

    protected function seedAssignmentsIfEmpty(): void
    {
        if (! $this->isLocalRuntime() || ! Schema::hasTable('handyman_assignments') || HandymanAssignment::query()->exists()) {
            return;
        }

        $orderId = $this->resolveOrderId();
        $executorProfileId = $this->resolveExecutorProfileId();

        if (! $orderId || ! $executorProfileId) {
            Log::info('Handyman assignments demo seed skipped: missing dependencies', [
                'order_id' => $orderId,
                'executor_profile_id' => $executorProfileId,
            ]);

            return;
        }

        $repairProjectId = Schema::hasTable('repair_projects') ? DB::table('repair_projects')->value('id') : null;
        if (! $repairProjectId) {
            $repairProjectId = RepairProject::query()->create([
                'order_id' => $orderId,
                'title' => 'Demo: проект по назначению',
                'status' => 'in_progress',
            ])->id;
        }

        HandymanAssignment::query()->create([
            'order_id' => $orderId,
            'executor_profile_id' => $executorProfileId,
            'repair_project_id' => $repairProjectId,
            'status' => 'accepted',
            'planned_start_at' => now()->subHours(2),
            'planned_finish_at' => now()->addHours(6),
            'score' => 5,
            'is_primary' => true,
            'meta' => ['source' => 'local_demo_seed'],
        ]);
    }

    protected function resolveOrderId(): ?int
    {
        if (! Schema::hasTable('orders')) {
            return null;
        }

        $query = DB::table('orders');
        if (Schema::hasColumn('orders', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        $orderId = $query->value('id');
        if ($orderId) {
            return (int) $orderId;
        }

        if (! Schema::hasTable('users')) {
            return null;
        }

        $userId = auth()->id() ?: DB::table('users')->value('id');
        if (! $userId) {
            return null;
        }

        $now = now();
        $orderNumber = 'HM-'.strtoupper(substr((string) str_replace('-', '', \Illuminate\Support\Str::uuid()), 0, 10));

        $payload = [
            'order_number' => $orderNumber,
            'user_id' => $userId,
            'status' => 'pending',
            'priority' => 'normal',
            'scheduled_at' => $now->copy()->addDay(),
            'total_amount' => 0,
            'currency' => 'NOK',
            'payment_status' => 'pending',
            'metadata' => json_encode(['source' => 'local_demo_seed', 'service_type' => 'handyman']),
            'created_at' => $now,
            'updated_at' => $now,
        ];

        if (Schema::hasColumn('orders', 'service_type')) {
            $payload['service_type'] = 'handyman';
        }

        if (! Schema::hasColumn('orders', 'metadata')) {
            unset($payload['metadata']);
        }

        try {
            return (int) DB::table('orders')->insertGetId($payload);
        } catch (\Throwable $exception) {
            Log::warning('Failed to create fallback order for handyman assignment seed', [
                'error' => $exception->getMessage(),
            ]);
        }

        return null;
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

        $payload = [
            'user_id' => $userId,
            'vehicle_type' => 'van',
            'rating' => 4.5,
            'completed_orders_count' => 5,
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if (Schema::hasColumn('executor_profiles', 'metadata')) {
            $payload['metadata'] = json_encode(['source' => 'local_demo_seed']);
        }

        DB::table('executor_profiles')->insert($payload);

        return (int) DB::table('executor_profiles')->where('user_id', $userId)->value('id');
    }

    protected function isLocalRuntime(): bool
    {
        $host = request()->getHost();

        return app()->environment('local') || in_array($host, ['127.0.0.1', 'localhost'], true);
    }
}
