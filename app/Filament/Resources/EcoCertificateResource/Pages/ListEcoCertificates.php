<?php

namespace App\Filament\Resources\EcoCertificateResource\Pages;

use App\Filament\Resources\EcoCertificateResource;
use App\Models\EcoCertificate;
use App\Models\Order;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ListEcoCertificates extends ListRecords
{
    protected static string $resource = EcoCertificateResource::class;

    public function mount(): void
    {
        $this->ensureEcoCertificatesSchema();
        parent::mount();
        $this->seedLocalEcoCertificatesIfEmpty();
    }

    protected function ensureEcoCertificatesSchema(): void
    {
        if (Schema::hasTable('eco_certificates')) {
            return;
        }

        try {
            Artisan::call('migrate', [
                '--path' => 'database/migrations/2025_11_16_120400_create_eco_certificates_table.php',
                '--force' => true,
            ]);
        } catch (\Throwable $exception) {
            Log::warning('Failed to auto-migrate eco_certificates table', [
                'error' => $exception->getMessage(),
            ]);
        }
    }

    protected function seedLocalEcoCertificatesIfEmpty(): void
    {
        if (! app()->environment('local') || ! Schema::hasTable('eco_certificates')) {
            return;
        }

        if (EcoCertificate::query()->exists()) {
            return;
        }

        try {
            $order = Order::query()->orderBy('id')->first();

            if (! $order) {
                return;
            }

            EcoCertificate::query()->create([
                'order_id' => $order->id,
                'certificate_uid' => 'ECO-'.strtoupper(Str::random(8)),
                'customer_name' => (string) ($order->user?->name ?? 'Demo Customer'),
                'summary_data' => [
                    'recycled_items' => 3,
                    'landfill_avoided_kg' => 42.7,
                    'notes' => 'Auto-generated local demo certificate',
                ],
                'co2_saved_kg' => 18.350,
                'items_reused_count' => 2,
                'issued_at' => now()->subMinutes(30),
                'pdf_path' => null,
            ]);
        } catch (\Throwable $exception) {
            Log::warning('Failed to seed local eco certificates', [
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
