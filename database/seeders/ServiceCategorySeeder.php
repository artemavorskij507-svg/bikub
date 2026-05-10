<?php

namespace Database\Seeders;

use App\Models\ServiceCategory;
use App\Models\ServiceType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ServiceCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create service categories
        $categories = [
            [
                'name' => 'Доставка',
                'code' => 'delivery',
                'slug' => 'delivery',
                'short_description' => 'Швидка та надійна доставка товарів та послуг',
                'description' => 'Швидка та надійна доставка товарів та послуг по всьому Нарвіку',
                'is_active' => true,
                'show_on_homepage' => true,
                'homepage_order' => 1,
                'icon_svg' => '<svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>',
            ],
            [
                'name' => 'Переїзд',
                'code' => 'moving',
                'slug' => 'moving',
                'short_description' => 'Професійні послуги переїзду та перевезення',
                'description' => 'Професійні послуги переїзду та перевезення меблів',
                'is_active' => true,
                'show_on_homepage' => true,
                'homepage_order' => 2,
                'icon_svg' => '<svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>',
            ],
            [
                'name' => 'Майстер',
                'code' => 'handyman',
                'slug' => 'handyman',
                'short_description' => 'Майстер на час для різних робіт',
                'description' => 'Майстер на час для різних робіт по дому та офісу',
                'is_active' => true,
                'show_on_homepage' => true,
                'homepage_order' => 3,
                'icon_svg' => '<svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>',
            ],
            [
                'name' => 'Еко-утилізація',
                'code' => 'eco',
                'slug' => 'eco',
                'short_description' => 'Екологічно чисте вивезення та утилізація',
                'description' => 'Екологічно чисте вивезення та утилізація старих речей',
                'is_active' => true,
                'show_on_homepage' => true,
                'homepage_order' => 4,
                'icon_svg' => '<svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
            ],
            [
                'name' => 'Евакуатор',
                'code' => 'tow',
                'slug' => 'tow',
                'short_description' => 'Екстрене евакуювання транспорту',
                'description' => 'Екстрене евакуювання транспорту та перевезення',
                'is_active' => true,
                'show_on_homepage' => true,
                'homepage_order' => 5,
                'icon_svg' => '<svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>',
            ],
            [
                'name' => 'Доручення',
                'code' => 'personal',
                'slug' => 'personal',
                'short_description' => 'Виконання особистих доручень',
                'description' => 'Виконання особистих доручень та покупок',
                'is_active' => true,
                'show_on_homepage' => true,
                'homepage_order' => 6,
                'icon_svg' => '<svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>',
            ],
            [
                'name' => 'Соціальна допомога',
                'code' => 'social',
                'slug' => 'social',
                'short_description' => 'Допомога людям похилого віку та з обмеженими можливостями',
                'description' => 'Допомога людям похилого віку та з обмеженими можливостями',
                'is_active' => true,
                'show_on_homepage' => true,
                'homepage_order' => 7,
                'icon_svg' => '<svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>',
            ],
        ];

        foreach ($categories as $categoryData) {
            ServiceCategory::updateOrCreate(
                ['code' => $categoryData['code']],
                array_merge($categoryData, [
                    'slug' => $categoryData['slug'] ?? Str::slug($categoryData['code'] ?? $categoryData['name']),
                ])
            );
        }

        // Create sample service types
        $careCategory = ServiceCategory::where('code', 'care')->first();
        $ecoCategory = ServiceCategory::where('code', 'eco')->first();
        $marketCategory = ServiceCategory::where('code', 'market')->first();
        $towCategory = ServiceCategory::where('code', 'tow')->first();

        $serviceTypes = [
            [
                'name' => 'Basic Bike Tune-up',
                'slug' => 'basic-tuneup',
                'description' => 'Essential bike maintenance including brake adjustment, gear tuning, and tire check',
                'default_pricing' => json_encode(['base_price' => 50.00]),
                'service_category_id' => $careCategory ? $careCategory->id : null,
                'is_active' => true,
            ],
            [
                'name' => 'Full Service',
                'slug' => 'full-service',
                'description' => 'Complete bike service including cleaning, lubrication, and safety check',
                'default_pricing' => json_encode(['base_price' => 120.00]),
                'service_category_id' => $careCategory ? $careCategory->id : null,
                'is_active' => true,
            ],
            [
                'name' => 'Eco-Friendly Cleaning',
                'slug' => 'eco-cleaning',
                'description' => 'Environmentally safe bike cleaning using biodegradable products',
                'default_pricing' => json_encode(['base_price' => 30.00]),
                'service_category_id' => $ecoCategory ? $ecoCategory->id : null,
                'is_active' => true,
            ],
            [
                'name' => 'Grocery Delivery',
                'slug' => 'grocery-delivery',
                'description' => 'Fast grocery delivery service',
                'default_pricing' => json_encode(['base_price' => 15.00]),
                'service_category_id' => $marketCategory ? $marketCategory->id : null,
                'is_active' => true,
            ],
            [
                'name' => 'Emergency Tow',
                'slug' => 'emergency-tow',
                'description' => 'Emergency bike towing service',
                'default_pricing' => json_encode(['base_price' => 80.00]),
                'service_category_id' => $towCategory ? $towCategory->id : null,
                'is_active' => true,
            ],
        ];

        foreach ($serviceTypes as $serviceData) {
            if ($serviceData['service_category_id']) {
                ServiceType::updateOrCreate(
                    ['slug' => $serviceData['slug']],
                    $serviceData
                );
            }
        }
    }
}
