<?php

namespace Database\Seeders;

use App\Models\Moving\ExecutorProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CouriersNarvikSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding Narvik Couriers...');

        $couriers = [
            [
                'name' => 'Marius Olsen',
                'phone' => '+47 400 10 301',
                'vehicle' => 'El-sykkel',
                'zone' => 'Narvik sentrum',
                'is_active' => true,
            ],
            [
                'name' => 'Sander Eriksen',
                'phone' => '+47 400 10 302',
                'vehicle' => 'Bil / VW Golf',
                'zone' => 'Narvik + Fagerneset',
                'is_active' => true,
            ],
            [
                'name' => 'Elias Berg',
                'phone' => '+47 400 10 303',
                'vehicle' => 'Bil / Toyota Yaris',
                'zone' => 'Ankenes',
                'is_active' => true,
            ],
        ];

        // Note: Roles are managed by Spatie Permission package
        // The role 'courier' should exist or be created separately

        foreach ($couriers as $courierData) {
            // Create or get user
            $email = strtolower(str_replace(' ', '.', $courierData['name'])).'@glf.no';
            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $courierData['name'],
                    'phone' => $courierData['phone'],
                    'password' => Hash::make('password'),
                    'is_active' => true,
                ]
            );

            // Assign courier role (if Spatie Permission is available)
            if (method_exists($user, 'hasRole') && ! $user->hasRole('courier')) {
                try {
                    $user->assignRole('courier');
                } catch (\Exception $e) {
                    // Role might not exist, skip for now
                    $this->command->warn("  ⚠ Could not assign 'courier' role to {$user->name}: {$e->getMessage()}");
                }
            }

            // Create or update ExecutorProfile
            $executorProfile = ExecutorProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'vehicle_type' => $this->mapVehicleType($courierData['vehicle']),
                    'skills' => ['delivery', 'grocery', 'food'],
                    'max_volume' => $this->getMaxVolume($courierData['vehicle']),
                    'max_weight' => $this->getMaxWeight($courierData['vehicle']),
                    'insurance_limit' => 100000,
                    'rating' => 4.5,
                    'completed_orders_count' => 0,
                    'is_active' => $courierData['is_active'],
                    'last_active_at' => now(),
                    'metadata' => [
                        'zone' => $courierData['zone'],
                        'vehicle' => $courierData['vehicle'],
                        'source' => 'narvik-delivery-seeder',
                    ],
                ]
            );

            $this->command->info("  ✓ Created/Updated courier: {$courierData['name']} ({$courierData['vehicle']})");
        }

        $this->command->info('✅ Narvik Couriers seeded successfully!');
    }

    private function mapVehicleType(string $vehicle): string
    {
        if (str_contains(strtolower($vehicle), 'sykkel') || str_contains(strtolower($vehicle), 'bike')) {
            return 'bike';
        }
        if (str_contains(strtolower($vehicle), 'bil') || str_contains(strtolower($vehicle), 'car')) {
            return 'car';
        }

        return 'van';
    }

    private function getMaxVolume(string $vehicle): float
    {
        if (str_contains(strtolower($vehicle), 'sykkel') || str_contains(strtolower($vehicle), 'bike')) {
            return 0.5; // m³
        }
        if (str_contains(strtolower($vehicle), 'bil') || str_contains(strtolower($vehicle), 'car')) {
            return 2.0; // m³
        }

        return 12.0; // m³ for van
    }

    private function getMaxWeight(string $vehicle): float
    {
        if (str_contains(strtolower($vehicle), 'sykkel') || str_contains(strtolower($vehicle), 'bike')) {
            return 30.0; // kg
        }
        if (str_contains(strtolower($vehicle), 'bil') || str_contains(strtolower($vehicle), 'car')) {
            return 200.0; // kg
        }

        return 800.0; // kg for van
    }
}
