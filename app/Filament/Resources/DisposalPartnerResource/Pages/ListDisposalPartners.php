<?php

namespace App\Filament\Resources\DisposalPartnerResource\Pages;

use App\Filament\Resources\DisposalPartnerResource;
use App\Models\DisposalPartner;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class ListDisposalPartners extends ListRecords
{
    protected static string $resource = DisposalPartnerResource::class;

    public function mount(): void
    {
        $this->ensureDisposalPartnersSchema();
        parent::mount();
        $this->seedLocalDisposalPartnersIfEmpty();
    }

    protected function ensureDisposalPartnersSchema(): void
    {
        if (Schema::hasTable('disposal_partners')) {
            return;
        }

        try {
            Artisan::call('migrate', [
                '--path' => 'database/migrations/2025_11_16_120100_create_disposal_partners_table.php',
                '--force' => true,
            ]);
        } catch (\Throwable $exception) {
            Log::warning('Failed to auto-migrate disposal_partners table', [
                'error' => $exception->getMessage(),
            ]);
        }
    }

    protected function seedLocalDisposalPartnersIfEmpty(): void
    {
        if (! app()->environment('local') || ! Schema::hasTable('disposal_partners')) {
            return;
        }

        if (DisposalPartner::query()->exists()) {
            return;
        }

        try {
            DisposalPartner::query()->create([
                'name' => 'Oslo Recycle Hub',
                'type' => 'RECYCLING_CENTER',
                'address' => 'Haralds vei 12',
                'city' => 'Oslo',
                'postal_code' => '0580',
                'latitude' => 59.9311000,
                'longitude' => 10.8062000,
                'opening_hours' => ['mon-fri' => '08:00-18:00', 'sat' => '10:00-15:00'],
                'accepted_categories' => ['furniture', 'electronics', 'small_appliance'],
                'requirements' => 'Large items should be disassembled when possible.',
                'licenses' => ['NOR-ECO-1122'],
                'contact_email' => 'contact@oslo-recycle.local',
                'contact_phone' => '+47 22 10 10 10',
                'is_active' => true,
            ]);

            DisposalPartner::query()->create([
                'name' => 'Green Charity Pickup',
                'type' => 'CHARITY',
                'address' => 'Storgata 44',
                'city' => 'Bergen',
                'postal_code' => '5015',
                'latitude' => 60.3931000,
                'longitude' => 5.3242000,
                'opening_hours' => ['mon-fri' => '09:00-17:00'],
                'accepted_categories' => ['furniture', 'textile', 'other'],
                'requirements' => 'Items must be clean and reusable.',
                'licenses' => ['NOR-CHARITY-09'],
                'contact_email' => 'team@green-charity.local',
                'contact_phone' => '+47 55 01 01 01',
                'is_active' => true,
            ]);

            DisposalPartner::query()->create([
                'name' => 'HazMat Processor North',
                'type' => 'HAZARDOUS_PROCESSOR',
                'address' => 'Industriveien 5',
                'city' => 'Trondheim',
                'postal_code' => '7040',
                'latitude' => 63.4305000,
                'longitude' => 10.3951000,
                'opening_hours' => ['mon-fri' => '07:00-16:00'],
                'accepted_categories' => ['hazardous', 'electronics'],
                'requirements' => 'Advance booking required for hazardous disposal.',
                'licenses' => ['NOR-HAZ-441'],
                'contact_email' => 'ops@hazmat-north.local',
                'contact_phone' => '+47 73 30 30 30',
                'is_active' => true,
            ]);
        } catch (\Throwable $exception) {
            Log::warning('Failed to seed local disposal partners', [
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
