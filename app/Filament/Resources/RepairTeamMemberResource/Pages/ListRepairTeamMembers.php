<?php

namespace App\Filament\Resources\RepairTeamMemberResource\Pages;

use App\Filament\Resources\RepairTeamMemberResource;
use App\Models\RepairProject;
use App\Models\RepairTeamMember;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class ListRepairTeamMembers extends ListRecords
{
    protected static string $resource = RepairTeamMemberResource::class;

    public function mount(): void
    {
        parent::mount();

        $this->ensureTeamSchema();
        $this->seedTeamMembersIfEmpty();
    }

    protected function ensureTeamSchema(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        $paths = [
            'database/migrations/2025_11_13_020853_create_executor_profiles_table.php',
            'database/migrations/2025_11_17_151100_create_repair_projects_table.php',
            'database/migrations/2025_11_17_151300_create_repair_team_members_table.php',
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
                Log::warning('Repair team schema bootstrap failed', [
                    'path' => $path,
                    'error' => $exception->getMessage(),
                ]);
            }
        }
    }

    protected function seedTeamMembersIfEmpty(): void
    {
        if (! app()->environment('local') || ! Schema::hasTable('repair_team_members') || RepairTeamMember::query()->exists()) {
            return;
        }

        $executorProfileId = $this->resolveExecutorProfileId();
        if (! $executorProfileId) {
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
                'title' => 'Demo: проект для команды',
                'status' => 'in_progress',
            ])->id;
        }

        RepairTeamMember::query()->create([
            'repair_project_id' => $repairProjectId,
            'executor_profile_id' => $executorProfileId,
            'role' => 'lead_handyman',
            'is_lead' => true,
            'notes' => 'Локальный демо-участник команды.',
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
            'vehicle_type' => 'van',
            'rating' => 4.6,
            'completed_orders_count' => 12,
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return (int) DB::table('executor_profiles')->where('user_id', $userId)->value('id');
    }
}
