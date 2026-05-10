<?php

namespace Database\Seeders;

use App\Models\Partner;
use Illuminate\Database\Seeder;

class RoadsideVehiclesNarvikSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding Narvik Roadside Vehicles...');

        $vehicles = [
            [
                'partner' => 'Narvik Bilberging AS',
                'type' => 'Tow Truck',
                'model' => 'Volvo FM330',
                'capacity_kg' => 6000,
            ],
            [
                'partner' => 'Frydenlund Bilservice',
                'type' => 'Service Van',
                'model' => 'Mercedes Sprinter',
                'capacity_kg' => 1200,
            ],
            [
                'partner' => 'Ofoten Road Rescue',
                'type' => 'Tow Truck',
                'model' => 'Scania P-series',
                'capacity_kg' => 7000,
            ],
        ];

        foreach ($vehicles as $vehicleData) {
            $partner = Partner::where('name', $vehicleData['partner'])->first();
            if (! $partner) {
                $this->command->warn("  ⚠ Partner not found: {$vehicleData['partner']}");

                continue;
            }

            // Store vehicle info in partner metadata
            $metadata = $partner->metadata ?? [];
            if (! isset($metadata['vehicles'])) {
                $metadata['vehicles'] = [];
            }

            // Check if vehicle already exists
            $vehicleExists = false;
            foreach ($metadata['vehicles'] as $existingVehicle) {
                if ($existingVehicle['model'] === $vehicleData['model']) {
                    $vehicleExists = true;
                    break;
                }
            }

            if (! $vehicleExists) {
                $metadata['vehicles'][] = [
                    'type' => $vehicleData['type'],
                    'model' => $vehicleData['model'],
                    'capacity_kg' => $vehicleData['capacity_kg'],
                    'is_active' => true,
                ];
                $partner->metadata = $metadata;
                $partner->save();
            }

            $this->command->info("  ✓ Added vehicle: {$vehicleData['model']} for {$vehicleData['partner']}");
        }

        $this->command->info('✅ Narvik Roadside Vehicles seeded successfully!');
    }
}
