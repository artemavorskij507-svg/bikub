<?php

namespace Database\Seeders;

use App\Models\Moving\ExecutorProfile;
use App\Models\SocialHelperProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SocialCareHelpersNarvikSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding Narvik Social Care Helpers...');

        $helpers = [
            [
                'name' => 'Eva Nystad',
                'phone' => '+47 400 55 201',
                'skills' => ['shopping_assist', 'home_care'],
                'zone' => 'Narvik sentrum',
            ],
            [
                'name' => 'Kari Stenersen',
                'phone' => '+47 400 55 202',
                'skills' => ['medical_escort', 'emotional_support'],
                'zone' => 'Ankenes',
            ],
            [
                'name' => 'Ida Moen',
                'phone' => '+47 400 55 203',
                'skills' => ['social_visit', 'home_care'],
                'zone' => 'Bjerkvik',
            ],
        ];

        foreach ($helpers as $helperData) {
            $email = strtolower(str_replace(' ', '.', $helperData['name'])).'@glf.no';

            // Create or get user
            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $helperData['name'],
                    'phone' => $helperData['phone'],
                    'password' => Hash::make('password'),
                    'is_active' => true,
                ]
            );

            // Create ExecutorProfile
            ExecutorProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'vehicle_type' => 'car',
                    'skills' => array_merge(['social_care'], $helperData['skills']),
                    'max_volume' => 0,
                    'max_weight' => 0,
                    'insurance_limit' => 100000,
                    'rating' => 4.8,
                    'completed_orders_count' => 0,
                    'is_active' => true,
                    'last_active_at' => now(),
                    'metadata' => [
                        'role' => 'social_helper',
                        'zone' => $helperData['zone'],
                        'source' => 'narvik-social-care-seeder',
                    ],
                ]
            );

            // Create SocialHelperProfile
            SocialHelperProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'level' => 'SOCIAL_HELPER',
                    'display_name' => $helperData['name'],
                    'bio' => "Социальный помощник в зоне {$helperData['zone']}",
                    'skills' => $helperData['skills'],
                    'has_police_certificate' => true,
                    'police_certificate_verified_at' => now()->subMonths(rand(1, 12)),
                    'first_aid_trained_at' => now()->subMonths(rand(6, 24)),
                    'rating_avg' => 4.8,
                    'rating_count' => rand(5, 25),
                    'is_active' => true,
                    'available_from' => now()->setTime(8, 0),
                    'available_to' => now()->setTime(18, 0),
                ]
            );

            $this->command->info("  ✓ Created/Updated helper: {$helperData['name']} ({$helperData['zone']})");
        }

        $this->command->info('✅ Narvik Social Care Helpers seeded successfully!');
    }
}
