<?php

namespace Database\Seeders;

use App\Models\DisposalPartner;
use App\Models\EcoTeam;
use Illuminate\Database\Seeder;

class EcoVehiclesNarvikSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding Narvik Eco Vehicles (via EcoTeams)...');

        $vehicles = [
            [
                'provider' => 'Narvik Renovasjon AS',
                'type' => 'Van',
                'model' => 'Mercedes Sprinter L3H2',
                'capacity_kg' => 1200,
            ],
            [
                'provider' => 'Ofoten Avfall',
                'type' => 'Truck',
                'model' => 'Volvo FL6',
                'capacity_kg' => 3500,
            ],
            [
                'provider' => 'Hålogaland Transport & Waste',
                'type' => 'Pickup',
                'model' => 'Toyota Hilux',
                'capacity_kg' => 900,
            ],
        ];

        foreach ($vehicles as $vehicleData) {
            $provider = DisposalPartner::where('name', $vehicleData['provider'])->first();
            if (! $provider) {
                $this->command->warn("  ⚠ Provider not found: {$vehicleData['provider']}");

                continue;
            }

            $vehicleType = match (strtolower($vehicleData['type'])) {
                'van' => 'van',
                'truck' => 'truck_large',
                'pickup' => 'van',
                default => 'van',
            };

            $capacityM3 = match (strtolower($vehicleData['type'])) {
                'van' => 12.0,
                'truck' => 25.0,
                'pickup' => 2.5,
                default => 12.0,
            };

            EcoTeam::updateOrCreate(
                [
                    'name' => "{$vehicleData['provider']} - {$vehicleData['model']}",
                ],
                [
                    'description' => "{$vehicleData['type']} vehicle for eco disposal services",
                    'vehicle_type' => $vehicleType,
                    'vehicle_capacity_m3' => $capacityM3,
                    'vehicle_max_weight_kg' => $vehicleData['capacity_kg'],
                    'is_active' => true,
                ]
            );

            $this->command->info("  ✓ Created/Updated vehicle: {$vehicleData['model']} for {$vehicleData['provider']}");
        }

        $this->command->info('✅ Narvik Eco Vehicles seeded successfully!');
    }
}
