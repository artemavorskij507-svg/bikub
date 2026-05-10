<?php

namespace App\Filament\Resources\VehicleInspectionRequestResource\Pages;

use App\Filament\Resources\VehicleInspectionRequestResource;
use App\Models\RoadHelperProfile;
use App\Models\User;
use App\Models\VehicleInspectionPreset;
use App\Models\VehicleInspectionRequest;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class ListVehicleInspectionRequests extends ListRecords
{
    protected static string $resource = VehicleInspectionRequestResource::class;

    public function mount(): void
    {
        parent::mount();
        $this->seedInspectionRequestsIfEmpty();
    }

    protected function getActions(): array
    {
        return [Actions\CreateAction::make()];
    }

    protected function seedInspectionRequestsIfEmpty(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        if (! Schema::hasTable('vehicle_inspection_requests') || ! Schema::hasTable('users')) {
            return;
        }

        if (VehicleInspectionRequest::query()->exists()) {
            return;
        }

        $preset = null;
        if (Schema::hasTable('vehicle_inspection_presets')) {
            $preset = VehicleInspectionPreset::query()->firstOrCreate(
                ['slug' => 'pre_purchase_basic'],
                [
                    'title' => 'Pre-purchase Basic',
                    'price' => 1490,
                    'description' => 'Базовый осмотр автомобиля перед покупкой.',
                    'checklist' => ['engine', 'transmission', 'body', 'electronics'],
                    'is_active' => true,
                    'sort_order' => 10,
                    'metadata' => json_encode(['source' => 'local_demo_seed'], JSON_UNESCAPED_UNICODE),
                ]
            );
        }

        $customer = User::query()->firstOrCreate(
            ['email' => 'inspection.customer@glf.local'],
            [
                'name' => 'Inspection Customer',
                'password' => Hash::make('6636'),
                'phone' => '+4799002201',
                'is_active' => true,
            ]
        );

        $helperId = null;
        if (Schema::hasTable('road_helper_profiles')) {
            $helperId = RoadHelperProfile::query()->value('id');
        }

        $rows = [
            [
                'customer_id' => $customer->id,
                'preset_id' => $preset?->id,
                'assigned_helper_id' => $helperId,
                'seller_name' => 'AutoHub Oslo',
                'seller_phone' => '+4799100001',
                'vehicle_make' => 'Toyota',
                'vehicle_model' => 'Corolla',
                'vehicle_year' => 2019,
                'vin_code' => 'JTDBR32E530057891',
                'address' => 'Dronning Eufemias gate 16, Oslo',
                'requested_time' => now()->addDay()->setTime(11, 0),
                'status' => 'new',
                'report_json' => null,
                'metadata' => ['source' => 'local_demo_seed'],
            ],
            [
                'customer_id' => $customer->id,
                'preset_id' => $preset?->id,
                'assigned_helper_id' => $helperId,
                'seller_name' => 'Nordic Cars',
                'seller_phone' => '+4799100002',
                'vehicle_make' => 'Volkswagen',
                'vehicle_model' => 'Passat',
                'vehicle_year' => 2018,
                'vin_code' => 'WVWZZZ3CZJE123456',
                'address' => 'Karl Johans gate 4, Oslo',
                'requested_time' => now()->addDays(2)->setTime(14, 30),
                'status' => 'assigned',
                'report_json' => null,
                'metadata' => ['source' => 'local_demo_seed'],
            ],
        ];

        foreach ($rows as $row) {
            VehicleInspectionRequest::query()->create($row);
        }
    }
}
