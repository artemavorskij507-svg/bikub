<?php

namespace Database\Seeders;

use App\Models\ServiceCategory;
use Illuminate\Database\Seeder;

class NarvikServiceCategoriesSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding Narvik service categories...');

        $categories = [
            ['slug' => 'delivery', 'code' => 'delivery', 'name' => ['nb' => 'Levering', 'en' => 'Delivery'], 'icon' => 'truck', 'sort_order' => 1],
            ['slug' => 'moving', 'code' => 'moving', 'name' => ['nb' => 'Flytting', 'en' => 'Moving'], 'icon' => 'box-arrow', 'sort_order' => 2],
            ['slug' => 'handyman', 'code' => 'handyman', 'name' => ['nb' => 'Håndverker', 'en' => 'Handyman'], 'icon' => 'tools', 'sort_order' => 3],
            ['slug' => 'eco', 'code' => 'eco', 'name' => ['nb' => 'Gjenbruk/Resirkulering', 'en' => 'Eco'], 'icon' => 'recycle', 'sort_order' => 4],
            ['slug' => 'social-help', 'code' => 'social-help', 'name' => ['nb' => 'Sosial hjelp', 'en' => 'Social Help'], 'icon' => 'hands-helping', 'sort_order' => 5],
            ['slug' => 'personal-task', 'code' => 'personal-task', 'name' => ['nb' => 'Personlig oppgave', 'en' => 'Personal Task'], 'icon' => 'user-clock', 'sort_order' => 6],
            ['slug' => 'tow', 'code' => 'tow', 'name' => ['nb' => 'Bilbergning', 'en' => 'Tow'], 'icon' => 'tow-truck', 'sort_order' => 7],
            ['slug' => 'food', 'code' => 'food', 'name' => ['nb' => 'Mat', 'en' => 'Food'], 'icon' => 'utensils', 'sort_order' => 8],
        ];

        foreach ($categories as $c) {
            $attrs = [
                'code' => $c['code'],
                'name' => $c['name'],
                'icon' => $c['icon'],
                'is_active' => true,
                'sort_order' => $c['sort_order'],
            ];

            ServiceCategory::updateOrCreate(
                ['slug' => $c['slug']],
                $attrs
            );

            $this->command->info("  ✓ Category: {$c['slug']} upserted");
        }

        $this->command->info('✅ Narvik service categories seeded.');
    }
}
