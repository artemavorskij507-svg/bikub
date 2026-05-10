<?php

namespace App\Filament\Resources\RoadsideEmergencyResource\Pages;

use App\Filament\Resources\RoadsideEmergencyResource;
use App\Models\Partner;
use App\Models\RoadHelperProfile;
use App\Models\RoadsideEmergency;
use App\Models\User;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class ListRoadsideEmergencies extends ListRecords
{
    protected static string $resource = RoadsideEmergencyResource::class;

    public function mount(): void
    {
        parent::mount();
        $this->seedRoadsideEmergenciesIfEmpty();
    }

    protected function getActions(): array
    {
        return [Actions\CreateAction::make()];
    }

    protected function seedRoadsideEmergenciesIfEmpty(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        if (! Schema::hasTable('roadside_emergencies') || ! Schema::hasTable('users')) {
            return;
        }

        if (RoadsideEmergency::query()->exists()) {
            return;
        }

        $customer = User::query()->firstOrCreate(
            ['email' => 'roadside.customer@glf.local'],
            [
                'name' => 'Roadside Customer',
                'password' => Hash::make('6636'),
                'phone' => '+4799003301',
                'is_active' => true,
            ]
        );

        $helperId = Schema::hasTable('road_helper_profiles')
            ? RoadHelperProfile::query()->value('id')
            : null;

        $partnerId = Schema::hasTable('partners')
            ? Partner::query()->roadside()->value('id')
            : null;

        $rows = [
            [
                'customer_id' => $customer->id,
                'road_helper_id' => $helperId,
                'resolved_by_partner_id' => null,
                'incident_type' => 'flat_tire',
                'incident_description' => 'Прокол правого переднего колеса, авто у обочины.',
                'photos' => [],
                'lat' => 59.9139,
                'lng' => 10.7522,
                'status' => 'new',
                'metadata' => ['source' => 'local_demo_seed', 'priority' => 'normal'],
            ],
            [
                'customer_id' => $customer->id,
                'road_helper_id' => $helperId,
                'resolved_by_partner_id' => $partnerId,
                'incident_type' => 'tow_needed',
                'incident_description' => 'Двигатель не запускается, нужен эвакуатор до СТО.',
                'photos' => [],
                'lat' => 59.9210,
                'lng' => 10.7810,
                'status' => 'assigned',
                'metadata' => ['source' => 'local_demo_seed', 'priority' => 'high'],
            ],
        ];

        foreach ($rows as $row) {
            RoadsideEmergency::query()->create($row);
        }
    }
}
