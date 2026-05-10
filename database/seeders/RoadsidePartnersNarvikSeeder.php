<?php

namespace Database\Seeders;

use App\Models\Partner;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RoadsidePartnersNarvikSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding Narvik Roadside & Tow Partners...');

        $partners = [
            [
                'name' => 'Narvik Bilberging AS',
                'address' => 'Skattørveien 40, 8517 Narvik',
                'phone' => '+47 769 80 111',
                'zone' => 'Narvik sentrum',
                'is_active' => true,
            ],
            [
                'name' => 'Frydenlund Bilservice',
                'address' => 'Frydenlundgata 12, 8517 Narvik',
                'phone' => '+47 769 44 222',
                'zone' => 'Narvik + Ankenes',
                'is_active' => true,
            ],
            [
                'name' => 'Ofoten Road Rescue',
                'address' => 'Bjerkvikveien 88, 8530 Bjerkvik',
                'phone' => '+47 901 33 444',
                'zone' => 'Bjerkvik + Ballangen',
                'is_active' => true,
            ],
        ];

        foreach ($partners as $partnerData) {
            $slug = Str::slug($partnerData['name']);

            Partner::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $partnerData['name'],
                    'type' => Partner::TYPE_TOWING_SERVICE,
                    'description' => 'Roadside assistance and towing service in Narvik region',
                    'contact_person' => $partnerData['name'],
                    'phone' => $partnerData['phone'],
                    'email' => strtolower(str_replace([' ', '&'], ['', 'and'], $partnerData['name'])).'@glf.no',
                    'address' => $partnerData['address'],
                    'latitude' => 68.43800, // Approximate coordinates for Narvik
                    'longitude' => 17.42700,
                    'active' => $partnerData['is_active'],
                    'is_active' => $partnerData['is_active'],
                    'is_available' => true,
                    'emergency_price_base' => 599.00,
                    'emergency_price_per_km' => 15.00,
                    'emergency_distance_km' => 60,
                    'on_time_rate' => 95.00,
                    'rating_avg' => 4.7,
                    'rating_count' => 0,
                    'sla_target_min' => 45,
                    'metadata' => [
                        'zone' => $partnerData['zone'],
                        'source' => 'narvik-roadside-seeder',
                        'capabilities' => ['tow', 'jump_start', 'tire_change', 'fuel_delivery', 'unlock'],
                    ],
                ]
            );

            $this->command->info("  ✓ Created/Updated: {$partnerData['name']}");
        }

        $this->command->info('✅ Narvik Roadside & Tow Partners seeded successfully!');
    }
}
