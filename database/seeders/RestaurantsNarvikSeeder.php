<?php

namespace Database\Seeders;

use App\Models\Restaurant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RestaurantsNarvikSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding Narvik Restaurants...');

        $restaurants = [
            [
                'name' => 'Peppes Pizza Narvik',
                'address' => 'Kongens gate 66, 8514 Narvik',
                'category' => 'Pizza',
                'delivery_time_min' => 25,
                'delivery_time_max' => 45,
                'is_active' => true,
            ],
            [
                'name' => 'Tind Restaurant',
                'address' => 'Kongens gate 33, Narvik',
                'category' => 'European',
                'delivery_time_min' => 20,
                'delivery_time_max' => 40,
                'is_active' => true,
            ],
            [
                'name' => 'Asia House Narvik',
                'address' => 'Dronningens gate 49',
                'category' => 'Asian',
                'delivery_time_min' => 30,
                'delivery_time_max' => 50,
                'is_active' => true,
            ],
        ];

        foreach ($restaurants as $restaurantData) {
            $slug = Str::slug($restaurantData['name']);

            Restaurant::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $restaurantData['name'],
                    'description' => "{$restaurantData['category']} restaurant in Narvik",
                    'cuisine_type' => strtolower($restaurantData['category']),
                    'address' => $restaurantData['address'],
                    'city' => 'Narvik',
                    'postcode' => $this->extractPostcode($restaurantData['address']),
                    'country' => 'Norway',
                    'latitude' => 68.43800, // Approximate coordinates for Narvik center
                    'longitude' => 17.42700,
                    'has_home_delivery' => false,
                    'has_takeaway' => true,
                    'supports_food_delivery' => true,
                    'average_delivery_time_minutes' => ($restaurantData['delivery_time_min'] + $restaurantData['delivery_time_max']) / 2,
                    'minimum_order_amount' => 150.00,
                    'delivery_fee' => 49.00,
                    'delivery_currency' => 'NOK',
                    'is_active' => $restaurantData['is_active'],
                    'metadata' => [
                        'source' => 'narvik-delivery-seeder',
                        'delivery_time_min' => $restaurantData['delivery_time_min'],
                        'delivery_time_max' => $restaurantData['delivery_time_max'],
                        'region' => 'Narvik +60km',
                    ],
                ]
            );

            $this->command->info("  ✓ Created/Updated: {$restaurantData['name']}");
        }

        $this->command->info('✅ Narvik Restaurants seeded successfully!');
    }

    private function extractPostcode(string $address): ?string
    {
        if (preg_match('/\b(\d{4})\b/', $address, $matches)) {
            return $matches[1];
        }

        return '8514'; // Default Narvik postcode
    }
}
