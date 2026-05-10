<?php

namespace Database\Seeders;

use App\Models\CarePlan;
use App\Models\CareService;
use App\Models\ClientProfile;
use App\Models\GeoZone;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SocialCarePlansNarvikSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding Narvik Social Care Plans...');

        // Get or create demo client users
        $clients = [
            ['name' => 'Liv Andersen', 'email' => 'liv.andersen@demo.no'],
            ['name' => 'Arne Pedersen', 'email' => 'arne.pedersen@demo.no'],
            ['name' => 'Nina Johansen', 'email' => 'nina.johansen@demo.no'],
        ];

        $narvikZone = GeoZone::where('slug', 'narvik-center')
            ->orWhere('name', 'like', '%Narvik%')
            ->first();

        $plans = [
            [
                'name' => 'Hjemmehjelp Basic',
                'description' => 'Базовая домашняя помощь: уборка, готовка, небольшие поручения',
                'weekly_hours' => 2,
                'status' => 'active',
                'client' => 'Liv Andersen',
                'service_code' => 'home_care',
            ],
            [
                'name' => 'Eldrehjelp Plus',
                'description' => 'Помощь пожилым: сопровождение, покупки, визиты в больницу',
                'weekly_hours' => 4,
                'status' => 'active',
                'client' => 'Arne Pedersen',
                'service_code' => 'medical_escort',
            ],
            [
                'name' => 'Funksjonsassistanse',
                'description' => 'Помощь людям с инвалидностью: сопровождение, бытовые задачи',
                'weekly_hours' => 3,
                'status' => 'active',
                'client' => 'Nina Johansen',
                'service_code' => 'social_visit',
            ],
        ];

        foreach ($plans as $planData) {
            // Find client
            $clientData = collect($clients)->firstWhere('name', $planData['client']);
            if (! $clientData) {
                $this->command->warn("  ⚠ Client not found: {$planData['client']}");

                continue;
            }

            $user = User::firstOrCreate(
                ['email' => $clientData['email']],
                [
                    'name' => $clientData['name'],
                    'password' => Hash::make('password'),
                    'phone' => '+47 '.rand(40000000, 49999999),
                    'is_active' => true,
                ]
            );

            $clientProfile = ClientProfile::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'full_name' => $clientData['name'],
                    'date_of_birth' => now()->subYears(rand(65, 85)),
                    'phone' => $user->phone,
                    'email' => $user->email,
                    'address_line' => 'Narvik',
                    'city' => 'Narvik',
                    'postal_code' => '8514',
                    'is_active' => true,
                ]
            );

            $careService = CareService::where('code', $planData['service_code'])->first();
            if (! $careService) {
                $this->command->warn("  ⚠ CareService not found: {$planData['service_code']}");

                continue;
            }

            CarePlan::updateOrCreate(
                [
                    'client_profile_id' => $clientProfile->id,
                    'care_service_id' => $careService->id,
                ],
                [
                    'service_type_code' => $planData['service_code'],
                    'frequency' => 'weekly',
                    'day_of_week' => 1, // Monday (0 = Sunday, 1 = Monday, etc.)
                    'time_of_day' => '10:00',
                    'duration_minutes' => $planData['weekly_hours'] * 60,
                    'starts_at' => now(),
                    'ends_at' => now()->addMonths(6),
                    'status' => $planData['status'],
                    'notes' => $planData['description'],
                ]
            );

            $this->command->info("  ✓ Created/Updated care plan: {$planData['name']} for {$planData['client']}");
        }

        $this->command->info('✅ Narvik Social Care Plans seeded successfully!');
    }
}
