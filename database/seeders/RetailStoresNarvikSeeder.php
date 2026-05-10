<?php

namespace Database\Seeders;

use App\Models\RetailStore;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RetailStoresNarvikSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding Narvik Retail Stores...');

        $stores = [
            [
                'name' => 'Rema 1000 Narvik',
                'address' => 'Dronningens gate 72, 8514 Narvik',
                'category' => 'Groceries',
                'lat' => 68.43886,
                'lng' => 17.42754,
                'is_active' => true,
            ],
            [
                'name' => 'Coop Extra Fagerneset',
                'address' => 'Fagernesveien 22, 8515 Narvik',
                'category' => 'Groceries',
                'lat' => 68.44098,
                'lng' => 17.37789,
                'is_active' => true,
            ],
            [
                'name' => 'Bunnpris Narvik Sentrum',
                'address' => 'Kongens gate 56, 8514 Narvik',
                'category' => 'Groceries',
                'lat' => 68.43801,
                'lng' => 17.42722,
                'is_active' => true,
            ],
            [
                'name' => 'Kiwi Ankenes',
                'address' => 'Ankenesveien 190, 8520 Ankenes',
                'category' => 'Groceries',
                'lat' => 68.42010,
                'lng' => 17.36520,
                'is_active' => true,
            ],
        ];

        foreach ($stores as $storeData) {
            $slug = Str::slug($storeData['name']);

            RetailStore::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $storeData['name'],
                    'description' => 'Grocery store in Narvik',
                    'category' => $storeData['category'],
                    'chain_name' => $this->extractChainName($storeData['name']),
                    'address' => $storeData['address'],
                    'city' => 'Narvik',
                    'postcode' => $this->extractPostcode($storeData['address']),
                    'country' => 'Norway',
                    'latitude' => $storeData['lat'],
                    'longitude' => $storeData['lng'],
                    'has_home_delivery' => false,
                    'supports_grocery_delivery' => true,
                    'supports_bulky_delivery' => false,
                    'is_active' => $storeData['is_active'],
                    'delivery_currency' => 'NOK',
                    'metadata' => [
                        'source' => 'narvik-delivery-seeder',
                        'region' => 'Narvik +60km',
                    ],
                ]
            );

            $this->command->info("  ✓ Created/Updated: {$storeData['name']}");
        }

        $this->command->info('✅ Narvik Retail Stores seeded successfully!');
    }

    private function extractChainName(string $name): string
    {
        if (str_contains($name, 'Rema 1000')) {
            return 'REMA 1000';
        }
        if (str_contains($name, 'Coop')) {
            return 'Coop';
        }
        if (str_contains($name, 'Bunnpris')) {
            return 'Bunnpris';
        }
        if (str_contains($name, 'Kiwi')) {
            return 'Kiwi';
        }

        return 'Unknown';
    }

    private function extractPostcode(string $address): ?string
    {
        if (preg_match('/\b(\d{4})\b/', $address, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
