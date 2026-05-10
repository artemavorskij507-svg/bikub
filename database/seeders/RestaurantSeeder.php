<?php

namespace Database\Seeders;

use App\Models\Restaurant;
use Illuminate\Database\Seeder;

class RestaurantSeeder extends Seeder
{
    public function run(): void
    {
        $restaurants = [
            ['Pizza Bakeren', 'pizza-bakeren', 'Pizza delivery', 'pizza', '+47 76 94 68 88', true, true],
            ['Fu Lam Restaurant', 'fu-lam-restaurant', 'Chinese cuisine', 'chinese', '+47 76 94 68 38', true, true],
            ['Furu Gastropub', 'furu-gastropub', 'Restaurant and bar', 'european', '+47 76 94 68 28', false, true],
            ['Rallars Pub & Kro', 'rallarn-pub-kro', 'Pub food', 'european', null, false, true],
            ['Totta Bar / Scandic Narvik', 'totta-bar-scandic', 'Hotel restaurant', 'european', null, false, true],
            ['Fiskekroken', 'fiskekroken', 'Seafood restaurant', 'seafood', null, false, true],
            ['Kafferiet Restaurant & Bar', 'kafferiet', 'Cafe restaurant', 'european', null, false, true],
            ['Narvikfjellet Mountainrestaurant', 'narvikfjellet', 'Mountain restaurant', 'european', null, false, true],
            ['Milano Restaurant Narvik', 'milano-narvik', 'Italian restaurant', 'italian', null, false, true],
            ['Linken Restaurant & Bar', 'linken', 'Restaurant and rooftop bar', 'european', null, false, true],
            ['Sushi Point', 'sushi-point', 'Japanese restaurant', 'japanese', null, false, true],
            ['Myklevold AMFI', 'myklevold-amfi', 'Shopping center restaurant', 'european', null, false, true],
            ['Duus Narvik', 'duus-narvik', 'Restaurant', 'european', null, false, true],
            ['Senterkafeen Narvik', 'senterkafeen', 'Cafe', 'cafe', null, false, true],
            ['Napoli Narvik', 'napoli-narvik', 'Italian restaurant', 'italian', null, false, true],
            ['Bella Napoli', 'bella-napoli', 'Italian restaurant', 'italian', null, false, true],
            ['Fiskehallen Narvik', 'fiskehallen', 'Fish restaurant', 'seafood', null, false, true],
            ['Viva Italia Narvik', 'viva-italia', 'Italian restaurant', 'italian', null, false, true],
            ['Arctic Train Café', 'arctic-train-cafe', 'Train station cafe', 'cafe', null, false, true],
            ['Totta Bar', 'totta-bar', 'Bar and restaurant', 'european', null, false, true],
            ['Burger King Narvik', 'burger-king-narvik', 'Fast food', 'fast-food', null, false, true],
            ['Thaikjøkken Narvik', 'thaikjokken', 'Thai restaurant', 'thai', null, false, true],
            ['Nordre Matbar Narvik', 'nordre-matbar', 'Food bar', 'european', null, false, true],
            ['Arilds Grillbar', 'arilds-grillbar', 'Grill bar', 'european', null, false, true],
            ['Stetind Hotell Restaurant', 'stetind-hotell', 'Hotel restaurant in Kjøpsvik', 'european', null, false, true],
        ];

        foreach ($restaurants as $restaurant) {
            Restaurant::updateOrCreate(
                ['slug' => $restaurant[1]],
                [
                    'name' => $restaurant[0],
                    'description' => $restaurant[2],
                    'cuisine_type' => $restaurant[3],
                    'phone' => $restaurant[4],
                    'has_home_delivery' => $restaurant[5],
                    'has_takeaway' => $restaurant[6],
                    'address' => 'Narvik, Norway',
                    'latitude' => 68.4372,
                    'longitude' => 17.4289,
                    'minimum_order_amount' => $restaurant[5] ? 150.00 : null,
                    'delivery_fee' => $restaurant[5] ? 49.00 : null,
                    'average_delivery_time_minutes' => $restaurant[5] ? 45 : null,
                    'is_active' => true,
                ]
            );
        }
    }
}
