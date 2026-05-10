<?php

namespace Database\Seeders;

use App\Models\GeoZone;
use App\Models\Restaurant;
use App\Models\RetailStore;
use App\Models\ServiceCategory;
use App\Models\ServiceType;
use Illuminate\Database\Seeder;

class RealWorldCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedServiceCategories();
        $this->seedServiceTypes();
        $this->seedGeoZones();
        $this->seedStores();
        $this->seedRestaurants();
    }

    protected function seedServiceCategories(): void
    {
        $categories = [
            [
                'code' => 'delivery',
                'slug' => 'delivery',
                'name' => 'Доставка',
                'description' => 'Продукты, крупногабарит, готовая еда и специальные доставки.',
                'icon' => 'heroicon-o-truck',
                'is_active' => true,
                'show_on_homepage' => true,
                'homepage_order' => 1,
                'sort_order' => 1,
            ],
            [
                'code' => 'moving',
                'slug' => 'moving',
                'name' => 'Переезд под ключ',
                'description' => 'Переезды, упаковка, погрузка/разгрузка, комплексные решения.',
                'icon' => 'heroicon-o-home-modern',
                'is_active' => true,
                'show_on_homepage' => true,
                'homepage_order' => 2,
                'sort_order' => 2,
            ],
            [
                'code' => 'eco',
                'slug' => 'eco',
                'name' => 'Эко-услуги и утилизация',
                'description' => 'Вывоз техники, мебели, мусора с акцентом на переработку и донейт.',
                'icon' => 'heroicon-o-recycle',
                'is_active' => true,
                'show_on_homepage' => true,
                'homepage_order' => 3,
                'sort_order' => 3,
            ],
            [
                'code' => 'handyman',
                'slug' => 'handyman',
                'name' => 'Мастер и ремонт',
                'description' => 'Мастер на час, ремонт, комплексные проекты.',
                'icon' => 'heroicon-o-briefcase',
                'is_active' => true,
                'show_on_homepage' => true,
                'homepage_order' => 4,
                'sort_order' => 4,
            ],
            [
                'code' => 'tow',
                'slug' => 'tow',
                'name' => 'Эвакуатор и дорога',
                'description' => 'Эвакуатор, помощь на дороге, осмотр автомобиля.',
                'icon' => 'heroicon-o-exclamation-triangle',
                'is_active' => true,
                'show_on_homepage' => true,
                'homepage_order' => 5,
                'sort_order' => 5,
            ],
            [
                'code' => 'personal-task',
                'slug' => 'personal-task',
                'name' => 'Индивидуальные поручения',
                'description' => 'Личный помощник: нестандартные поручения и задачи.',
                'icon' => 'heroicon-o-user',
                'is_active' => true,
                'show_on_homepage' => true,
                'homepage_order' => 6,
                'sort_order' => 6,
            ],
            [
                'code' => 'social-help',
                'slug' => 'social-help',
                'name' => 'Социальная помощь и забота',
                'description' => 'Помощь пожилым, людям с особыми потребностями, доверенные визиты.',
                'icon' => 'heroicon-o-heart',
                'is_active' => true,
                'show_on_homepage' => true,
                'homepage_order' => 7,
                'sort_order' => 7,
            ],
        ];

        foreach ($categories as $data) {
            ServiceCategory::updateOrCreate(
                ['code' => $data['code']],
                $data
            );
        }

        $this->command?->info('Seeded '.count($categories).' service categories.');
    }

    protected function seedServiceTypes(): void
    {
        $deliveryCategory = ServiceCategory::where('code', 'delivery')->first();

        if (! $deliveryCategory) {
            $this->command?->warn('Delivery category not found. Skipping service types.');

            return;
        }

        $serviceTypes = [
            [
                'code' => 'grocery',
                'slug' => 'grocery',
                'name' => 'Доставка продуктов',
                'description' => 'Доставка продуктов из магазинов',
                'category' => 'delivery',
                'service_category_id' => $deliveryCategory->id,
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'code' => 'bulky',
                'slug' => 'bulky',
                'name' => 'Доставка крупногабарита',
                'description' => 'Доставка мебели, техники и крупногабаритных товаров',
                'category' => 'delivery',
                'service_category_id' => $deliveryCategory->id,
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'code' => 'food',
                'slug' => 'food',
                'name' => 'Доставка готовой еды',
                'description' => 'Доставка еды из ресторанов и кафе',
                'category' => 'delivery',
                'service_category_id' => $deliveryCategory->id,
                'is_active' => true,
                'sort_order' => 3,
            ],
        ];

        foreach ($serviceTypes as $data) {
            ServiceType::updateOrCreate(
                ['code' => $data['code']],
                $data
            );
        }

        $this->command?->info('Seeded '.count($serviceTypes).' delivery service types.');
    }

    protected function seedGeoZones(): void
    {
        $zones = [
            [
                'slug' => 'narvik-sentrum',
                'name' => 'Narvik Sentrum',
                'description' => 'Центр Нарвика и ближайшие кварталы.',
                'type' => 'service_area',
                'center_latitude' => 68.4380,
                'center_longitude' => 17.4270,
                'radius_meters' => 3000,
                'is_active' => true,
                'metadata' => [
                    'base_delivery_fee' => 49,
                    'extra_km_fee' => 8,
                    'city' => 'Narvik',
                    'country' => 'Norway',
                ],
            ],
            [
                'slug' => 'ankenes',
                'name' => 'Ankenes',
                'description' => 'Анкенес и прилегающие районы.',
                'type' => 'service_area',
                'center_latitude' => 68.4315,
                'center_longitude' => 17.4290,
                'radius_meters' => 4000,
                'is_active' => true,
                'metadata' => [
                    'base_delivery_fee' => 59,
                    'extra_km_fee' => 10,
                    'city' => 'Narvik',
                    'country' => 'Norway',
                ],
            ],
            [
                'slug' => 'fagernes',
                'name' => 'Fagernes',
                'description' => 'Fagernes и окрестности.',
                'type' => 'service_area',
                'center_latitude' => 68.4200,
                'center_longitude' => 17.4100,
                'radius_meters' => 5000,
                'is_active' => true,
                'metadata' => [
                    'base_delivery_fee' => 69,
                    'extra_km_fee' => 12,
                    'city' => 'Narvik',
                    'country' => 'Norway',
                ],
            ],
            [
                'slug' => 'beisfjord',
                'name' => 'Beisfjord',
                'description' => 'Долина Beisfjord.',
                'type' => 'service_area',
                'center_latitude' => 68.4100,
                'center_longitude' => 17.4000,
                'radius_meters' => 6000,
                'is_active' => true,
                'metadata' => [
                    'base_delivery_fee' => 79,
                    'extra_km_fee' => 12,
                    'city' => 'Narvik',
                    'country' => 'Norway',
                ],
            ],
            [
                'slug' => 'halogaland',
                'name' => 'Hålogaland',
                'description' => 'Мост и прилегающие зоны.',
                'type' => 'service_area',
                'center_latitude' => 68.4450,
                'center_longitude' => 17.4350,
                'radius_meters' => 3500,
                'is_active' => true,
                'metadata' => [
                    'base_delivery_fee' => 69,
                    'extra_km_fee' => 11,
                    'city' => 'Narvik',
                    'country' => 'Norway',
                ],
            ],
            [
                'slug' => 'forstadsone',
                'name' => 'Forstadsone',
                'description' => 'Условная пригородная зона вокруг Нарвика.',
                'type' => 'service_area',
                'center_latitude' => 68.4500,
                'center_longitude' => 17.4400,
                'radius_meters' => 8000,
                'is_active' => true,
                'metadata' => [
                    'base_delivery_fee' => 89,
                    'extra_km_fee' => 14,
                    'city' => 'Narvik',
                    'country' => 'Norway',
                ],
            ],
        ];

        foreach ($zones as $data) {
            GeoZone::updateOrCreate(
                ['slug' => $data['slug']],
                $data
            );
        }

        $this->command?->info('Seeded '.count($zones).' geo zones.');
    }

    protected function seedStores(): void
    {
        $stores = [
            [
                'name' => 'Rema 1000 Narvik',
                'slug' => 'rema-1000-narvik',
                'brand' => 'REMA 1000',
                'chain_name' => 'REMA 1000',
                'category' => 'grocery',
                'address' => 'Bolagsgata, Narvik',
                'city' => 'Narvik',
                'postcode' => '8514',
                'country' => 'Norway',
                'latitude' => 68.4380,
                'longitude' => 17.4270,
                'is_active' => true,
                'supports_grocery_delivery' => true,
                'supports_bulky_delivery' => false,
                'has_home_delivery' => true,
            ],
            [
                'name' => 'Kiwi Narvik',
                'slug' => 'kiwi-narvik',
                'brand' => 'KIWI',
                'chain_name' => 'KIWI',
                'category' => 'grocery',
                'address' => 'Dronningens gate, Narvik',
                'city' => 'Narvik',
                'postcode' => '8514',
                'country' => 'Norway',
                'latitude' => 68.4385,
                'longitude' => 17.4300,
                'is_active' => true,
                'supports_grocery_delivery' => true,
                'supports_bulky_delivery' => false,
                'has_home_delivery' => true,
            ],
            [
                'name' => 'Coop Extra Narvik',
                'slug' => 'coop-extra-narvik',
                'brand' => 'Coop Extra',
                'chain_name' => 'Coop Extra',
                'category' => 'grocery',
                'address' => 'Kongens gate, Narvik',
                'city' => 'Narvik',
                'postcode' => '8514',
                'country' => 'Norway',
                'latitude' => 68.4390,
                'longitude' => 17.4320,
                'is_active' => true,
                'supports_grocery_delivery' => true,
                'supports_bulky_delivery' => false,
                'has_home_delivery' => true,
            ],
            [
                'name' => 'Coop Prix Narvik',
                'slug' => 'coop-prix-narvik',
                'brand' => 'Coop Prix',
                'chain_name' => 'Coop Prix',
                'category' => 'grocery',
                'address' => 'Narvik-area',
                'city' => 'Narvik',
                'postcode' => '8514',
                'country' => 'Norway',
                'latitude' => 68.4375,
                'longitude' => 17.4250,
                'is_active' => true,
                'supports_grocery_delivery' => true,
                'supports_bulky_delivery' => false,
                'has_home_delivery' => true,
            ],
            [
                'name' => 'Joker Narvik',
                'slug' => 'joker-narvik',
                'brand' => 'Joker',
                'chain_name' => 'Joker',
                'category' => 'grocery',
                'address' => 'Narvik-area',
                'city' => 'Narvik',
                'postcode' => '8514',
                'country' => 'Norway',
                'latitude' => 68.4370,
                'longitude' => 17.4230,
                'is_active' => true,
                'supports_grocery_delivery' => true,
                'supports_bulky_delivery' => false,
                'has_home_delivery' => true,
            ],
            [
                'name' => 'Bunnpris Narvik',
                'slug' => 'bunnpris-narvik',
                'brand' => 'Bunnpris',
                'chain_name' => 'Bunnpris',
                'category' => 'grocery',
                'address' => 'Narvik-area',
                'city' => 'Narvik',
                'postcode' => '8514',
                'country' => 'Norway',
                'latitude' => 68.4365,
                'longitude' => 17.4210,
                'is_active' => true,
                'supports_grocery_delivery' => true,
                'supports_bulky_delivery' => false,
                'has_home_delivery' => true,
            ],
            [
                'name' => 'Spar Narvik',
                'slug' => 'spar-narvik',
                'brand' => 'Spar',
                'chain_name' => 'Spar',
                'category' => 'grocery',
                'address' => 'Narvik-area',
                'city' => 'Narvik',
                'postcode' => '8514',
                'country' => 'Norway',
                'latitude' => 68.4355,
                'longitude' => 17.4200,
                'is_active' => true,
                'supports_grocery_delivery' => true,
                'supports_bulky_delivery' => false,
                'has_home_delivery' => true,
            ],
            [
                'name' => 'Eurospar Narvik',
                'slug' => 'eurospar-narvik',
                'brand' => 'Eurospar',
                'chain_name' => 'Eurospar',
                'category' => 'grocery',
                'address' => 'Narvik-area',
                'city' => 'Narvik',
                'postcode' => '8514',
                'country' => 'Norway',
                'latitude' => 68.4350,
                'longitude' => 17.4180,
                'is_active' => true,
                'supports_grocery_delivery' => true,
                'supports_bulky_delivery' => false,
                'has_home_delivery' => true,
            ],
        ];

        foreach ($stores as $data) {
            RetailStore::updateOrCreate(
                ['slug' => $data['slug']],
                $data
            );
        }

        $this->command?->info('Seeded '.count($stores).' retail stores.');
    }

    protected function seedRestaurants(): void
    {
        $restaurants = [
            [
                'name' => 'Narvik Sushi',
                'slug' => 'narvik-sushi',
                'brand' => 'Narvik Sushi',
                'cuisine_type' => 'sushi',
                'address' => 'Narvik Sentrum',
                'city' => 'Narvik',
                'postcode' => '8514',
                'country' => 'Norway',
                'latitude' => 68.4382,
                'longitude' => 17.4290,
                'is_active' => true,
                'supports_food_delivery' => true,
                'has_home_delivery' => true,
            ],
            [
                'name' => 'Pizza Narvik',
                'slug' => 'pizza-narvik',
                'brand' => 'Pizza Narvik',
                'cuisine_type' => 'pizza',
                'address' => 'Narvik Sentrum',
                'city' => 'Narvik',
                'postcode' => '8514',
                'country' => 'Norway',
                'latitude' => 68.4384,
                'longitude' => 17.4285,
                'is_active' => true,
                'supports_food_delivery' => true,
                'has_home_delivery' => true,
            ],
            [
                'name' => 'Kebab House Narvik',
                'slug' => 'kebab-house-narvik',
                'brand' => 'Kebab House',
                'cuisine_type' => 'kebab',
                'address' => 'Narvik Sentrum',
                'city' => 'Narvik',
                'postcode' => '8514',
                'country' => 'Norway',
                'latitude' => 68.4386,
                'longitude' => 17.4280,
                'is_active' => true,
                'supports_food_delivery' => true,
                'has_home_delivery' => true,
            ],
            [
                'name' => 'Thai Express Narvik',
                'slug' => 'thai-express-narvik',
                'brand' => 'Thai Express',
                'cuisine_type' => 'thai',
                'address' => 'Narvik Sentrum',
                'city' => 'Narvik',
                'postcode' => '8514',
                'country' => 'Norway',
                'latitude' => 68.4388,
                'longitude' => 17.4275,
                'is_active' => true,
                'supports_food_delivery' => true,
                'has_home_delivery' => true,
            ],
            [
                'name' => 'Cafe Aurora',
                'slug' => 'cafe-aurora',
                'brand' => 'Cafe Aurora',
                'cuisine_type' => 'cafe',
                'address' => 'Narvik Sentrum',
                'city' => 'Narvik',
                'postcode' => '8514',
                'country' => 'Norway',
                'latitude' => 68.4390,
                'longitude' => 17.4270,
                'is_active' => true,
                'supports_food_delivery' => true,
                'has_home_delivery' => true,
            ],
            [
                'name' => 'Nordic Burger',
                'slug' => 'nordic-burger',
                'brand' => 'Nordic Burger',
                'cuisine_type' => 'burger',
                'address' => 'Narvik Sentrum',
                'city' => 'Narvik',
                'postcode' => '8514',
                'country' => 'Norway',
                'latitude' => 68.4392,
                'longitude' => 17.4265,
                'is_active' => true,
                'supports_food_delivery' => true,
                'has_home_delivery' => true,
            ],
            [
                'name' => 'Family Diner Narvik',
                'slug' => 'family-diner-narvik',
                'brand' => 'Family Diner',
                'cuisine_type' => 'home',
                'address' => 'Narvik-area',
                'city' => 'Narvik',
                'postcode' => '8514',
                'country' => 'Norway',
                'latitude' => 68.4378,
                'longitude' => 17.4255,
                'is_active' => true,
                'supports_food_delivery' => true,
                'has_home_delivery' => true,
            ],
            [
                'name' => 'Indian Spice Narvik',
                'slug' => 'indian-spice-narvik',
                'brand' => 'Indian Spice',
                'cuisine_type' => 'indian',
                'address' => 'Narvik-area',
                'city' => 'Narvik',
                'postcode' => '8514',
                'country' => 'Norway',
                'latitude' => 68.4372,
                'longitude' => 17.4245,
                'is_active' => true,
                'supports_food_delivery' => true,
                'has_home_delivery' => true,
            ],
            [
                'name' => 'Taco Fjord',
                'slug' => 'taco-fjord',
                'brand' => 'Taco Fjord',
                'cuisine_type' => 'mexican',
                'address' => 'Narvik-area',
                'city' => 'Narvik',
                'postcode' => '8514',
                'country' => 'Norway',
                'latitude' => 68.4368,
                'longitude' => 17.4235,
                'is_active' => true,
                'supports_food_delivery' => true,
                'has_home_delivery' => true,
            ],
            [
                'name' => 'Bakery Narvik',
                'slug' => 'bakery-narvik',
                'brand' => 'Bakery Narvik',
                'cuisine_type' => 'bakery',
                'address' => 'Narvik-area',
                'city' => 'Narvik',
                'postcode' => '8514',
                'country' => 'Norway',
                'latitude' => 68.4362,
                'longitude' => 17.4225,
                'is_active' => true,
                'supports_food_delivery' => true,
                'has_home_delivery' => true,
            ],
            [
                'name' => 'Chinese Garden Narvik',
                'slug' => 'chinese-garden-narvik',
                'brand' => 'Chinese Garden',
                'cuisine_type' => 'chinese',
                'address' => 'Narvik-area',
                'city' => 'Narvik',
                'postcode' => '8514',
                'country' => 'Norway',
                'latitude' => 68.4358,
                'longitude' => 17.4215,
                'is_active' => true,
                'supports_food_delivery' => true,
                'has_home_delivery' => true,
            ],
            [
                'name' => 'Fish & Chips Narvik',
                'slug' => 'fish-chips-narvik',
                'brand' => 'Fish & Chips',
                'cuisine_type' => 'seafood',
                'address' => 'Narvik-area',
                'city' => 'Narvik',
                'postcode' => '8514',
                'country' => 'Norway',
                'latitude' => 68.4352,
                'longitude' => 17.4205,
                'is_active' => true,
                'supports_food_delivery' => true,
                'has_home_delivery' => true,
            ],
            [
                'name' => 'Steakhouse Narvik',
                'slug' => 'steakhouse-narvik',
                'brand' => 'Steakhouse Narvik',
                'cuisine_type' => 'steakhouse',
                'address' => 'Narvik-area',
                'city' => 'Narvik',
                'postcode' => '8514',
                'country' => 'Norway',
                'latitude' => 68.4348,
                'longitude' => 17.4195,
                'is_active' => true,
                'supports_food_delivery' => true,
                'has_home_delivery' => true,
            ],
            [
                'name' => 'Pasta Corner Narvik',
                'slug' => 'pasta-corner-narvik',
                'brand' => 'Pasta Corner',
                'cuisine_type' => 'italian',
                'address' => 'Narvik-area',
                'city' => 'Narvik',
                'postcode' => '8514',
                'country' => 'Norway',
                'latitude' => 68.4342,
                'longitude' => 17.4185,
                'is_active' => true,
                'supports_food_delivery' => true,
                'has_home_delivery' => true,
            ],
        ];

        foreach ($restaurants as $data) {
            Restaurant::updateOrCreate(
                ['slug' => $data['slug']],
                $data
            );
        }

        $this->command?->info('Seeded '.count($restaurants).' restaurants.');
    }
}
