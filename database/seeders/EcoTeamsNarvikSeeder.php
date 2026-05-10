<?php

namespace Database\Seeders;

use App\Models\EcoTeam;
use App\Models\Moving\ExecutorProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class EcoTeamsNarvikSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding Narvik Eco Teams...');

        $teams = [
            [
                'name' => 'Eco Team A — Narvik',
                'vehicle' => 'Mercedes Sprinter L3H2',
                'zone' => 'Narvik',
                'members' => [
                    ['name' => 'Andreas Lund', 'phone' => '+47 400 20 101'],
                    ['name' => 'Kristoffer Jensen', 'phone' => '+47 400 20 102'],
                ],
            ],
            [
                'name' => 'Eco Team B — Ankenes',
                'vehicle' => 'Volvo FL6',
                'zone' => 'Ankenes',
                'members' => [
                    ['name' => 'Odin Johansen', 'phone' => '+47 400 20 103'],
                    ['name' => 'Martin Dagsvik', 'phone' => '+47 400 20 104'],
                ],
            ],
        ];

        foreach ($teams as $teamData) {
            // Find or create EcoTeam
            $ecoTeam = EcoTeam::where('name', $teamData['name'])->first();

            if (! $ecoTeam) {
                // Try to find by vehicle model
                $ecoTeam = EcoTeam::where('description', 'like', "%{$teamData['vehicle']}%")->first();
            }

            if (! $ecoTeam) {
                $vehicleType = str_contains($teamData['vehicle'], 'Sprinter') ? 'van' : 'truck_large';
                $capacityM3 = str_contains($teamData['vehicle'], 'Sprinter') ? 12.0 : 25.0;
                $maxWeight = str_contains($teamData['vehicle'], 'Sprinter') ? 1200 : 3500;

                $ecoTeam = EcoTeam::create([
                    'name' => $teamData['name'],
                    'description' => "Eco disposal team with {$teamData['vehicle']}",
                    'vehicle_type' => $vehicleType,
                    'vehicle_capacity_m3' => $capacityM3,
                    'vehicle_max_weight_kg' => $maxWeight,
                    'is_active' => true,
                ]);
            }

            // Create users and executor profiles for team members
            foreach ($teamData['members'] as $member) {
                $email = strtolower(str_replace(' ', '.', $member['name'])).'@glf.no';
                $user = User::updateOrCreate(
                    ['email' => $email],
                    [
                        'name' => $member['name'],
                        'phone' => $member['phone'],
                        'password' => Hash::make('password'),
                        'is_active' => true,
                    ]
                );

                // Create ExecutorProfile for eco executor
                ExecutorProfile::updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'vehicle_type' => $ecoTeam->vehicle_type ?? 'van',
                        'skills' => ['eco_disposal', 'waste_management', 'heavy_lifting'],
                        'max_volume' => $ecoTeam->vehicle_capacity_m3 ?? 12.0,
                        'max_weight' => $ecoTeam->vehicle_max_weight_kg ?? 1200.0,
                        'insurance_limit' => 200000,
                        'rating' => 4.5,
                        'completed_orders_count' => 0,
                        'is_active' => true,
                        'last_active_at' => now(),
                        'metadata' => [
                            'eco_team_id' => $ecoTeam->id,
                            'eco_team_name' => $ecoTeam->name,
                            'zone' => $teamData['zone'],
                            'source' => 'narvik-eco-seeder',
                        ],
                    ]
                );

                // Note: team_user table references 'teams' table, not 'eco_teams'
                // We'll store team info in ExecutorProfile metadata instead
                // If you need direct team-user linking, create eco_team_user pivot table

                $this->command->info("  ✓ Created/Updated team member: {$member['name']} for {$teamData['name']}");
            }
        }

        $this->command->info('✅ Narvik Eco Teams seeded successfully!');
    }
}
