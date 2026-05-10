<?php

namespace Database\Seeders;

use App\Models\GeoZone;
use App\Models\Restaurant;
use App\Models\RetailStore;
use App\Models\ServiceCategory;
use Illuminate\Database\Seeder;

class BikubeDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding Bikube Demo Data...');

        // 1) Базовые категории услуг (уже должны быть через RealWorldCatalogSeeder, но проверим)
        $categories = [
            ['slug' => 'delivery', 'name' => 'Доставка', 'is_active' => true, 'homepage_order' => 1],
            ['slug' => 'moving', 'name' => 'Переезд под ключ', 'is_active' => true, 'homepage_order' => 2],
            ['slug' => 'handyman', 'name' => 'Мастер на час', 'is_active' => true, 'homepage_order' => 3],
            ['slug' => 'eco', 'name' => 'Эко-услуги и утилизация', 'is_active' => true, 'homepage_order' => 4],
            ['slug' => 'social-help', 'name' => 'Социальная помощь', 'is_active' => true, 'homepage_order' => 5],
            ['slug' => 'personal-task', 'name' => 'Индивидуальные поручения', 'is_active' => true, 'homepage_order' => 6],
            ['slug' => 'tow', 'name' => 'Эвакуатор и помощь на дороге', 'is_active' => true, 'homepage_order' => 7],
        ];

        foreach ($categories as $cat) {
            ServiceCategory::updateOrCreate(
                ['slug' => $cat['slug']],
                array_merge($cat, [
                    'code' => $cat['slug'],
                    'show_on_homepage' => true,
                    'description' => 'Услуги категории '.$cat['name'],
                ])
            );
        }

        // 2) Магазины Нарвика (дополняем существующие)
        $stores = [
            ['name' => 'Rema 1000 Narvik', 'slug' => 'rema-1000-narvik', 'brand' => 'Rema 1000'],
            ['name' => 'Kiwi Narvik', 'slug' => 'kiwi-narvik', 'brand' => 'Kiwi'],
            ['name' => 'Coop Extra Narvik', 'slug' => 'coop-extra-narvik', 'brand' => 'Coop Extra'],
            ['name' => 'Spar Narvik', 'slug' => 'spar-narvik', 'brand' => 'Spar'],
        ];

        foreach ($stores as $s) {
            RetailStore::updateOrCreate(
                ['slug' => $s['slug']],
                array_merge($s, [
                    'is_active' => true,
                    'supports_grocery_delivery' => true,
                    'city' => 'Narvik',
                    'country' => 'NO',
                    'latitude' => 68.4372,
                    'longitude' => 17.4289,
                    'address' => 'Narvik, Norway',
                ])
            );
        }

        // 3) Рестораны (дополняем существующие)
        $restaurants = [
            ['name' => 'Peppes Pizza Narvik', 'slug' => 'peppes-pizza-narvik', 'cuisine_type' => 'pizza'],
            ['name' => 'Burger King Narvik', 'slug' => 'burger-king-narvik', 'cuisine_type' => 'burger'],
            ['name' => 'Lokalt Kafé', 'slug' => 'lokalt-kafe', 'cuisine_type' => 'cafe'],
        ];

        foreach ($restaurants as $r) {
            Restaurant::updateOrCreate(
                ['slug' => $r['slug']],
                array_merge($r, [
                    'is_active' => true,
                    'supports_food_delivery' => true,
                    'city' => 'Narvik',
                    'country' => 'NO',
                    'latitude' => 68.4372,
                    'longitude' => 17.4289,
                    'address' => 'Narvik, Norway',
                ])
            );
        }

        // 4) Геозоны Нарвика (уже должны быть через RealWorldCatalogSeeder)
        $zones = [
            ['slug' => 'narvik-center', 'name' => 'Narvik sentrum'],
            ['slug' => 'narvik-sentrum', 'name' => 'Narvik Sentrum'],
        ];

        foreach ($zones as $z) {
            GeoZone::updateOrCreate(
                ['slug' => $z['slug']],
                array_merge($z, [
                    'is_active' => true,
                    'center_latitude' => 68.4372,
                    'center_longitude' => 17.4289,
                    'radius_meters' => 2000,
                ])
            );
        }

        $this->command->info('Bikube Demo Data seeded successfully!');
    }
}
