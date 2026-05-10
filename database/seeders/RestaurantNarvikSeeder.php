<?php

namespace Database\Seeders;

use App\Models\Restaurant;
use Illuminate\Database\Seeder;

class RestaurantNarvikSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $restaurants = [
            [
                'name' => 'Kafferiet Restobar',
                'brand' => 'Kafferiet',
                'slug' => 'kafferiet-narvik',
                'cuisine_type' => 'Scandinavian',
                'address' => 'Kongens gate 16',
                'city' => 'Narvik',
                'postcode' => '8514',
                'country' => 'Norway',
                'latitude' => 68.4387,
                'longitude' => 17.4265,
                'phone' => '+47 76 94 23 00',
                'supports_food_delivery' => true,
                'has_home_delivery' => true,
                'delivery_metadata' => [
                    'min_order_amount' => 250,
                    'free_delivery_over' => 900,
                    'opening_hours' => [
                        'mon_thu' => '11:00-22:00',
                        'fri_sat' => '11:00-01:00',
                        'sun' => '13:00-21:00',
                    ],
                ],
            ],
            [
                'name' => 'Peppe\'s Pizza Narvik',
                'brand' => 'Peppe\'s Pizza',
                'slug' => 'peppes-pizza-narvik',
                'cuisine_type' => 'Pizza',
                'address' => 'Kongens gate 49',
                'city' => 'Narvik',
                'postcode' => '8514',
                'country' => 'Norway',
                'latitude' => 68.4389,
                'longitude' => 17.4286,
                'phone' => '+47 22 22 55 55',
                'supports_food_delivery' => true,
                'has_home_delivery' => true,
                'delivery_metadata' => [
                    'min_order_amount' => 199,
                    'opening_hours' => [
                        'mon_thu' => '11:00-22:00',
                        'fri_sat' => '11:00-23:00',
                        'sun' => '12:00-22:00',
                    ],
                ],
            ],
            [
                'name' => 'Narvik Sushi & Grill',
                'brand' => 'Narvik Sushi',
                'slug' => 'narvik-sushi',
                'cuisine_type' => 'Sushi',
                'address' => 'Dronningens gate 25',
                'city' => 'Narvik',
                'postcode' => '8514',
                'country' => 'Norway',
                'latitude' => 68.4392,
                'longitude' => 17.4279,
                'phone' => '+47 76 95 03 20',
                'supports_food_delivery' => true,
                'has_home_delivery' => true,
                'delivery_metadata' => [
                    'min_order_amount' => 300,
                    'opening_hours' => [
                        'mon_thu' => '12:00-21:00',
                        'fri_sat' => '12:00-22:00',
                        'sun' => '14:00-21:00',
                    ],
                ],
            ],
            [
                'name' => 'Tind Restaurant & Bar',
                'brand' => 'Tind',
                'slug' => 'tind-restaurant',
                'cuisine_type' => 'Nordic',
                'address' => 'Kirkegata 8',
                'city' => 'Narvik',
                'postcode' => '8516',
                'country' => 'Norway',
                'latitude' => 68.4370,
                'longitude' => 17.4268,
                'supports_food_delivery' => false,
                'has_takeaway' => true,
                'delivery_metadata' => [
                    'takeaway_only' => true,
                ],
            ],
        ];

        foreach ($restaurants as $data) {
            Restaurant::updateOrCreate(
                ['slug' => $data['slug']],
                $data
            );
        }

        $this->command?->info('Seeded '.count($restaurants).' Narvik restaurants.');
    }
}
