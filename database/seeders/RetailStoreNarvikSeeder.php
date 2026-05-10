<?php

namespace Database\Seeders;

use App\Models\RetailStore;
use Illuminate\Database\Seeder;

class RetailStoreNarvikSeeder extends Seeder
{
    public function run(): void
    {
        $stores = [
            [
                'name' => 'REMA 1000 Narvik Sentrum',
                'brand' => 'REMA 1000',
                'slug' => 'rema1000-narvik',
                'category' => 'grocery',
                'address' => 'Kongens gate 52',
                'city' => 'Narvik',
                'postcode' => '8514',
                'country' => 'Norway',
                'latitude' => 68.4385,
                'longitude' => 17.4275,
                'supports_grocery_delivery' => true,
                'supports_bulky_delivery' => false,
                'delivery_metadata' => [
                    'min_order_amount' => 200,
                    'free_delivery_over' => 800,
                    'opening_hours' => [
                        'mon_fri' => '08:00-22:00',
                        'sat' => '09:00-21:00',
                        'sun' => null,
                    ],
                ],
            ],
            [
                'name' => 'KIWI Narvik',
                'brand' => 'KIWI',
                'slug' => 'kiwi-narvik',
                'category' => 'grocery',
                'address' => 'Kongens gate 50',
                'city' => 'Narvik',
                'postcode' => '8514',
                'country' => 'Norway',
                'latitude' => 68.4387,
                'longitude' => 17.4288,
                'supports_grocery_delivery' => true,
                'supports_bulky_delivery' => false,
                'delivery_metadata' => [
                    'min_order_amount' => 150,
                    'free_delivery_over' => 700,
                    'opening_hours' => [
                        'mon_fri' => '07:00-23:00',
                        'sat' => '08:00-21:00',
                        'sun' => null,
                    ],
                ],
            ],
            [
                'name' => 'Coop Extra Narvik',
                'brand' => 'Coop Extra',
                'slug' => 'coop-extra-narvik',
                'category' => 'grocery',
                'address' => 'Frydenlundgata 2',
                'city' => 'Narvik',
                'postcode' => '8517',
                'country' => 'Norway',
                'latitude' => 68.4380,
                'longitude' => 17.4305,
                'supports_grocery_delivery' => true,
                'supports_bulky_delivery' => false,
                'delivery_metadata' => [
                    'min_order_amount' => 200,
                    'free_delivery_over' => 900,
                    'opening_hours' => [
                        'mon_fri' => '07:00-23:00',
                        'sat' => '08:00-21:00',
                        'sun' => null,
                    ],
                ],
            ],
            [
                'name' => 'Elkjøp Narvik',
                'brand' => 'Elkjøp',
                'slug' => 'elkjoep-narvik',
                'category' => 'electronics',
                'address' => 'Ankenesveien 41',
                'city' => 'Narvik',
                'postcode' => '8520',
                'country' => 'Norway',
                'latitude' => 68.4315,
                'longitude' => 17.4290,
                'supports_grocery_delivery' => false,
                'supports_bulky_delivery' => true,
                'delivery_metadata' => [
                    'bulky' => [
                        'supports_inhouse_delivery' => true,
                        'supports_assembly' => true,
                    ],
                ],
            ],
            [
                'name' => 'JYSK Narvik',
                'brand' => 'JYSK',
                'slug' => 'jysk-narvik',
                'category' => 'home',
                'address' => 'Parkhallveien 1',
                'city' => 'Narvik',
                'postcode' => '8510',
                'country' => 'Norway',
                'latitude' => 68.4300,
                'longitude' => 17.4350,
                'supports_grocery_delivery' => false,
                'supports_bulky_delivery' => true,
                'delivery_metadata' => [
                    'bulky' => [
                        'supports_inhouse_delivery' => true,
                        'supports_old_furniture_disposal' => true,
                    ],
                ],
            ],
        ];

        foreach ($stores as $data) {
            $payload = array_merge($data, [
                'chain_name' => $data['brand'],
                'has_home_delivery' => $data['supports_grocery_delivery'] ?? false,
            ]);

            RetailStore::updateOrCreate(
                ['slug' => $payload['slug']],
                $payload
            );
        }

        $this->command?->info('Seeded '.count($stores).' Narvik retail stores.');
    }
}
