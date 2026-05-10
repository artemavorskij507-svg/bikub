<?php

namespace Database\Seeders;

use App\Models\Moving\ExecutorProfile;
use App\Models\Partner;
use App\Models\RoadHelperProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RoadsideHelpersNarvikSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding Narvik Roadside Helpers...');

        $helpers = [
            [
                'name' => 'Jonas Haug',
                'phone' => '+47 400 88 101',
                'role' => 'tow_operator',
                'partner' => 'Narvik Bilberging AS',
            ],
            [
                'name' => 'Lars Mikkelsen',
                'phone' => '+47 400 88 102',
                'role' => 'roadside_tech',
                'partner' => 'Frydenlund Bilservice',
            ],
            [
                'name' => 'Emil Kristoffersen',
                'phone' => '+47 400 88 103',
                'role' => 'tow_operator',
                'partner' => 'Ofoten Road Rescue',
            ],
        ];

        foreach ($helpers as $helperData) {
            $partner = Partner::where('name', $helperData['partner'])->first();
            if (! $partner) {
                $this->command->warn("  ⚠ Partner not found: {$helperData['partner']}");

                continue;
            }

            // Create or get user
            $email = strtolower(str_replace(' ', '.', $helperData['name'])).'@glf.no';
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
                    'vehicle_type' => $helperData['role'] === 'tow_operator' ? 'truck' : 'van',
                    'skills' => ['roadside_assistance', 'towing', 'vehicle_repair'],
                    'max_volume' => 0, // Not applicable for roadside
                    'max_weight' => $helperData['role'] === 'tow_operator' ? 7000 : 1200,
                    'insurance_limit' => 500000,
                    'rating' => 4.8,
                    'completed_orders_count' => 0,
                    'is_active' => true,
                    'last_active_at' => now(),
                    'metadata' => [
                        'roadside_partner_id' => $partner->id,
                        'roadside_partner_name' => $partner->name,
                        'role' => $helperData['role'],
                        'source' => 'narvik-roadside-seeder',
                    ],
                ]
            );

            // Create RoadHelperProfile
            RoadHelperProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'vehicle_type' => $helperData['role'] === 'tow_operator' ? 'tow_truck' : 'service_van',
                    'vehicle_model' => $helperData['role'] === 'tow_operator' ? 'Tow Truck' : 'Service Van',
                    'equipment' => ['tow_equipment', 'jump_start_cables', 'tire_tools'],
                    'skills' => ['towing', 'roadside_assistance', 'vehicle_repair'],
                    'current_status' => 'idle',
                    'location_lat' => 68.43800,
                    'location_lng' => 17.42700,
                    'metadata' => [
                        'roadside_partner_id' => $partner->id,
                        'roadside_partner_name' => $partner->name,
                        'role' => $helperData['role'],
                        'phone' => $helperData['phone'],
                        'source' => 'narvik-roadside-seeder',
                    ],
                ]
            );

            $this->command->info("  ✓ Created/Updated helper: {$helperData['name']} ({$helperData['role']}) for {$helperData['partner']}");
        }

        $this->command->info('✅ Narvik Roadside Helpers seeded successfully!');
    }
}
