<?php

namespace Database\Seeders;

use App\Models\Moving\ExecutorProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ErrandWorkersNarvikSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding Narvik Errand Workers...');

        $workers = [
            [
                'name' => 'Liam Karlsen',
                'phone' => '+47 400 55 301',
                'transport' => 'Bike',
                'skills' => ['purchase_and_deliver', 'pickup_and_drop'],
                'zone' => 'Narvik sentrum',
            ],
            [
                'name' => 'Marlene Håkonsen',
                'phone' => '+47 400 55 302',
                'transport' => 'Car (Toyota Auris)',
                'skills' => ['document_service', 'pharmacy', 'special_errand'],
                'zone' => 'Ankenes',
            ],
            [
                'name' => 'Sivert Mo',
                'phone' => '+47 400 55 303',
                'transport' => 'On Foot / Bus',
                'skills' => ['pickup_and_drop', 'document_service'],
                'zone' => 'Bjerkvik',
            ],
        ];

        foreach ($workers as $workerData) {
            $email = strtolower(str_replace(' ', '.', $workerData['name'])).'@glf.no';

            // Create or get user
            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $workerData['name'],
                    'phone' => $workerData['phone'],
                    'password' => Hash::make('password'),
                    'is_active' => true,
                ]
            );

            // Create ExecutorProfile
            ExecutorProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'vehicle_type' => str_contains(strtolower($workerData['transport']), 'car') ? 'car' : (str_contains(strtolower($workerData['transport']), 'bike') ? 'bike' : 'foot'),
                    'skills' => array_merge(['errand_runner'], $workerData['skills']),
                    'max_volume' => 0,
                    'max_weight' => 0,
                    'insurance_limit' => 50000,
                    'rating' => 4.7,
                    'completed_orders_count' => 0,
                    'is_active' => true,
                    'last_active_at' => now(),
                    'metadata' => [
                        'role' => 'errand_runner',
                        'transport' => $workerData['transport'],
                        'zone' => $workerData['zone'],
                        'source' => 'narvik-errand-workers-seeder',
                    ],
                ]
            );

            $this->command->info("  ✓ Created/Updated errand worker: {$workerData['name']} ({$workerData['transport']}, {$workerData['zone']})");
        }

        $this->command->info('✅ Narvik Errand Workers seeded successfully!');
    }
}
