<?php

namespace Database\Seeders;

use App\Models\ServiceCategory;
use Illuminate\Database\Seeder;

class MainDirectionsSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Доставка',
                'slug' => 'delivery',
                'short_description' => 'Доставка покупок, еды и товаров по региону.',
                'icon' => 'heroicon-o-shopping-cart',
                'color' => 'blue',
                'order_column' => 10,
                'is_active' => true,
            ],
            [
                'name' => 'Переезд под ключ',
                'slug' => 'moving',
                'short_description' => 'Упаковка, перевозка, вывоз, перемещение, утилизация.',
                'icon' => 'heroicon-o-truck',
                'color' => 'amber',
                'order_column' => 20,
                'is_active' => true,
            ],
            [
                'name' => 'Еко-Услуги',
                'slug' => 'eco',
                'short_description' => 'Вывоз техники, мебели, сортировка и утилизация.',
                'icon' => 'heroicon-o-sparkles',
                'color' => 'green',
                'order_column' => 30,
                'is_active' => true,
            ],
            [
                'name' => 'Майстер-Услуги',
                'slug' => 'handyman',
                'short_description' => 'Майстер на годину, збірка, монтаж, дрібний ремонт.',
                'icon' => 'heroicon-o-briefcase',
                'color' => 'orange',
                'order_column' => 40,
                'is_active' => true,
            ],
            [
                'name' => 'Евакуатор',
                'slug' => 'tow',
                'short_description' => 'Помощь на дороге, эвакуация авто.',
                'icon' => 'heroicon-o-shield-exclamation',
                'color' => 'red',
                'order_column' => 50,
                'is_active' => true,
            ],
            [
                'name' => 'Индивидуальные Поручения',
                'slug' => 'personal-task',
                'short_description' => '"Сделайте за меня": документы, покупки, проверки.',
                'icon' => 'heroicon-o-document-check',
                'color' => 'indigo',
                'order_column' => 60,
                'is_active' => true,
            ],
            [
                'name' => 'Социальная Помощь',
                'slug' => 'social-help',
                'short_description' => 'Сопровождение, помощь пожилым и уязвимым.',
                'icon' => 'heroicon-o-heart',
                'color' => 'purple',
                'order_column' => 70,
                'is_active' => true,
            ],
        ];

        foreach ($categories as $data) {
            $payload = array_merge($data, ['code' => $data['slug']]);

            ServiceCategory::updateOrCreate(
                ['slug' => $data['slug']],
                $payload
            );
        }
    }
}
