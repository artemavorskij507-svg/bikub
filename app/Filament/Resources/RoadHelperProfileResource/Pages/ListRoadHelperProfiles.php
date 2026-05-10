<?php

namespace App\Filament\Resources\RoadHelperProfileResource\Pages;

use App\Filament\Resources\RoadHelperProfileResource;
use App\Models\RoadHelperProfile;
use App\Models\User;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class ListRoadHelperProfiles extends ListRecords
{
    protected static string $resource = RoadHelperProfileResource::class;

    public function mount(): void
    {
        parent::mount();
        $this->seedRoadHelpersIfEmpty();
    }

    protected function getActions(): array
    {
        return [Actions\CreateAction::make()];
    }

    protected function seedRoadHelpersIfEmpty(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        if (! Schema::hasTable('road_helper_profiles') || ! Schema::hasTable('users')) {
            return;
        }

        if (RoadHelperProfile::query()->exists()) {
            return;
        }

        $helpers = [
            [
                'name' => 'Road Helper Alex',
                'email' => 'roadhelper.alex@glf.local',
                'phone' => '+4799001101',
                'vehicle_type' => 'van',
                'vehicle_model' => 'Mercedes Sprinter',
                'vehicle_number' => 'RH-101',
                'equipment' => ['jump_starter', 'air_compressor', 'tow_rope'],
                'skills' => ['battery_jumpstart', 'tire_change', 'fuel_delivery'],
                'current_status' => 'idle',
                'location_lat' => 59.9139,
                'location_lng' => 10.7522,
            ],
            [
                'name' => 'Road Helper Nina',
                'email' => 'roadhelper.nina@glf.local',
                'phone' => '+4799001102',
                'vehicle_type' => 'pickup',
                'vehicle_model' => 'Toyota Hilux',
                'vehicle_number' => 'RH-102',
                'equipment' => ['winch', 'hydraulic_jack', 'toolkit'],
                'skills' => ['vehicle_transport', 'minor_repairs'],
                'current_status' => 'on_route',
                'location_lat' => 59.9210,
                'location_lng' => 10.7810,
            ],
            [
                'name' => 'Road Helper Marius',
                'email' => 'roadhelper.marius@glf.local',
                'phone' => '+4799001103',
                'vehicle_type' => 'tow_truck',
                'vehicle_model' => 'Iveco Daily Tow',
                'vehicle_number' => 'RH-103',
                'equipment' => ['flatbed', 'dolly', 'safety_lights'],
                'skills' => ['tow_service', 'accident_recovery'],
                'current_status' => 'idle',
                'location_lat' => 59.8990,
                'location_lng' => 10.7680,
            ],
        ];

        foreach ($helpers as $helper) {
            $user = User::query()->firstOrCreate(
                ['email' => $helper['email']],
                [
                    'name' => $helper['name'],
                    'password' => Hash::make('6636'),
                    'phone' => $helper['phone'],
                    'is_active' => true,
                ]
            );

            RoadHelperProfile::query()->firstOrCreate(
                ['user_id' => $user->id],
                [
                    'vehicle_type' => $helper['vehicle_type'],
                    'vehicle_model' => $helper['vehicle_model'],
                    'vehicle_number' => $helper['vehicle_number'],
                    'equipment' => $helper['equipment'],
                    'skills' => $helper['skills'],
                    'current_status' => $helper['current_status'],
                    'location_lat' => $helper['location_lat'],
                    'location_lng' => $helper['location_lng'],
                    'metadata' => ['source' => 'local_demo_seed'],
                ]
            );
        }
    }
}
