<?php

namespace App\Filament\Resources\DeliveryZoneResource\Pages;

use App\Filament\Resources\DeliveryZoneResource;
use App\Models\DeliveryZone;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Throwable;

class ListDeliveryZones extends ListRecords
{
    protected static string $resource = DeliveryZoneResource::class;

    public function mount(): void
    {
        $this->ensureDeliveryZonesTableForLocal();

        parent::mount();

        $this->seedDeliveryZonesIfEmpty();
    }

    protected function getActions(): array
    {
        return [Actions\CreateAction::make()];
    }

    protected function ensureDeliveryZonesTableForLocal(): void
    {
        if (! app()->environment('local') || Schema::hasTable('delivery_zones')) {
            return;
        }

        Schema::create('delivery_zones', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('type')->default('polygon');
            $table->decimal('center_lat', 10, 7)->nullable();
            $table->decimal('center_lng', 10, 7)->nullable();
            $table->decimal('radius_km', 8, 2)->nullable();
            $table->json('geometry_data')->nullable();
            $table->json('coordinates')->nullable();
            $table->boolean('is_active')->default(true);
            $table->decimal('delivery_fee', 10, 2)->default(0);
            $table->unsignedInteger('delivery_time_minutes')->default(30);
            $table->timestamps();
        });
    }

    protected function seedDeliveryZonesIfEmpty(): void
    {
        if (! app()->environment('local') || ! Schema::hasTable('delivery_zones')) {
            return;
        }

        if (DeliveryZone::query()->exists()) {
            return;
        }

        try {
            DeliveryZone::query()->create([
                'name' => 'Oslo Central Zone',
                'type' => 'circle',
                'center_lat' => 59.9139,
                'center_lng' => 10.7522,
                'radius_km' => 5.0,
                'delivery_fee' => 79.00,
                'delivery_time_minutes' => 35,
                'is_active' => true,
                'coordinates' => [
                    [59.9139, 10.7522],
                ],
                'geometry_data' => [
                    'source' => 'local_demo_seed',
                ],
            ]);
        } catch (Throwable) {
            // Keep admin page usable if local seed can't be inserted.
        }
    }
}
