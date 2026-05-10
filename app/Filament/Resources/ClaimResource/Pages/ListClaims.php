<?php

namespace App\Filament\Resources\ClaimResource\Pages;

use App\Filament\Resources\ClaimResource;
use App\Models\Claim;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class ListClaims extends ListRecords
{
    protected static string $resource = ClaimResource::class;

    public function mount(): void
    {
        parent::mount();

        $this->ensureClaimsSchema();
        $this->seedClaimsIfEmpty();
    }

    protected function ensureClaimsSchema(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        $paths = [
            'database/migrations/2025_11_17_151600_create_claims_table.php',
            'database/migrations/2025_11_18_163201_add_sla_fields_to_claims_table.php',
            'database/migrations/2025_11_18_163203_create_claim_messages_table.php',
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
                Log::warning('Claims schema bootstrap failed', [
                    'path' => $path,
                    'error' => $exception->getMessage(),
                ]);
            }
        }
    }

    protected function seedClaimsIfEmpty(): void
    {
        if (! app()->environment('local') || ! Schema::hasTable('claims') || Claim::query()->exists()) {
            return;
        }

        $userId = DB::table('users')->value('id');
        if (! $userId) {
            return;
        }

        $orderId = Schema::hasTable('orders') ? DB::table('orders')->value('id') : null;
        $now = now();

        Claim::query()->create([
            'user_id' => $userId,
            'order_id' => $orderId,
            'opened_by_user_id' => $userId,
            'assigned_to_user_id' => $userId,
            'type' => 'quality',
            'status' => 'open',
            'severity' => 'medium',
            'title' => 'Demo: проверка качества услуги',
            'description' => 'Локальная демо-претензия для страницы /admin/claims.',
            'opened_at' => $now->copy()->subHours(2),
            'sla_response_due_at' => $now->copy()->addHours(2),
            'sla_resolution_due_at' => $now->copy()->addDay(),
            'meta' => ['source' => 'local_demo_seed'],
        ]);
    }
}
