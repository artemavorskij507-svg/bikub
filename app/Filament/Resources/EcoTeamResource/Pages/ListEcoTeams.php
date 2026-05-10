<?php

namespace App\Filament\Resources\EcoTeamResource\Pages;

use App\Filament\Resources\EcoTeamResource;
use App\Models\EcoTeam;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class ListEcoTeams extends ListRecords
{
    protected static string $resource = EcoTeamResource::class;

    public function mount(): void
    {
        $this->ensureEcoTeamsSchema();
        parent::mount();
        $this->seedLocalEcoTeamsIfEmpty();
    }

    protected function ensureEcoTeamsSchema(): void
    {
        if (Schema::hasTable('eco_teams')) {
            return;
        }

        try {
            Artisan::call('migrate', [
                '--path' => 'database/migrations/2025_11_16_120200_create_eco_teams_table.php',
                '--force' => true,
            ]);
        } catch (\Throwable $exception) {
            Log::warning('Failed to auto-migrate eco_teams table', [
                'error' => $exception->getMessage(),
            ]);
        }
    }

    protected function seedLocalEcoTeamsIfEmpty(): void
    {
        if (! app()->environment('local') || ! Schema::hasTable('eco_teams')) {
            return;
        }

        if (EcoTeam::query()->exists()) {
            return;
        }

        try {
            EcoTeam::query()->create([
                'name' => 'Eco Team Oslo #1',
                'description' => 'Urban pickup crew for furniture and electronics.',
                'vehicle_type' => 'van',
                'vehicle_capacity_m3' => 9.5,
                'vehicle_max_weight_kg' => 1200,
                'is_active' => true,
            ]);

            EcoTeam::query()->create([
                'name' => 'Eco Team Bergen Heavy',
                'description' => 'Heavy-load crew for bulky disposal jobs.',
                'vehicle_type' => 'truck_large',
                'vehicle_capacity_m3' => 22.0,
                'vehicle_max_weight_kg' => 6500,
                'is_active' => true,
            ]);

            EcoTeam::query()->create([
                'name' => 'Eco Team Trondheim Light',
                'description' => 'Fast response team for light daily routes.',
                'vehicle_type' => 'truck_small',
                'vehicle_capacity_m3' => 14.0,
                'vehicle_max_weight_kg' => 3200,
                'is_active' => true,
            ]);
        } catch (\Throwable $exception) {
            Log::warning('Failed to seed local eco teams', [
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
