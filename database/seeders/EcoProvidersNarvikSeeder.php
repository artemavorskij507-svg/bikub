<?php

namespace Database\Seeders;

use App\Models\DisposalPartner;
use Illuminate\Database\Seeder;

class EcoProvidersNarvikSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding Narvik Eco Disposal Providers...');

        $providers = [
            [
                'name' => 'Narvik Renovasjon AS',
                'address' => 'Bjørkveien 12, 8517 Narvik',
                'phone' => '+47 769 60 800',
                'zone' => 'Narvik sentrum',
                'is_active' => true,
            ],
            [
                'name' => 'Ofoten Avfall',
                'address' => 'Ankenesveien 130, 8520 Ankenes',
                'phone' => '+47 769 30 444',
                'zone' => 'Narvik + Ankenes',
                'is_active' => true,
            ],
            [
                'name' => 'Hålogaland Transport & Waste',
                'address' => 'Fagernesveien 74, 8514 Narvik',
                'phone' => '+47 400 55 102',
                'zone' => 'Narvik + Bjerkvik',
                'is_active' => true,
            ],
        ];

        foreach ($providers as $providerData) {
            DisposalPartner::updateOrCreate(
                ['name' => $providerData['name']],
                [
                    'type' => 'RECYCLING_CENTER',
                    'address' => $providerData['address'],
                    'city' => 'Narvik',
                    'postal_code' => $this->extractPostcode($providerData['address']),
                    'latitude' => 68.43800, // Approximate coordinates for Narvik
                    'longitude' => 17.42700,
                    'opening_hours' => [
                        'monday' => ['08:00', '16:00'],
                        'tuesday' => ['08:00', '16:00'],
                        'wednesday' => ['08:00', '16:00'],
                        'thursday' => ['08:00', '16:00'],
                        'friday' => ['08:00', '16:00'],
                        'saturday' => ['09:00', '14:00'],
                        'sunday' => null,
                    ],
                    'accepted_categories' => ['furniture', 'large_appliance', 'electronics', 'mixed', 'hazardous'],
                    'requirements' => 'Items must be accessible. Heavy items require assistance.',
                    'licenses' => ['waste_management', 'transport'],
                    'contact_email' => strtolower(str_replace([' ', '&'], ['', 'and'], $providerData['name'])).'@glf.no',
                    'contact_phone' => $providerData['phone'],
                    'is_active' => $providerData['is_active'],
                ]
            );

            $this->command->info("  ✓ Created/Updated: {$providerData['name']}");
        }

        $this->command->info('✅ Narvik Eco Disposal Providers seeded successfully!');
    }

    private function extractPostcode(string $address): ?string
    {
        if (preg_match('/\b(\d{4})\b/', $address, $matches)) {
            return $matches[1];
        }

        return '8514'; // Default Narvik postcode
    }
}
