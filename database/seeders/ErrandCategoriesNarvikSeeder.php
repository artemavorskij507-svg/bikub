<?php

namespace Database\Seeders;

use App\Models\ServiceCategory;
use App\Models\ServiceType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ErrandCategoriesNarvikSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding Errand Categories...');

        // Get or create errands category
        $category = ServiceCategory::firstOrCreate(
            ['code' => 'errands'],
            [
                'name' => 'Индивидуальные поручения',
                'slug' => 'errands',
                'description' => 'Персональные поручения и задачи',
                'is_active' => true,
                'show_on_homepage' => true,
            ]
        );

        $categories = [
            [
                'name' => 'Купить и доставить',
                'code' => 'purchase_and_deliver',
                'base_price' => 79,
            ],
            [
                'name' => 'Забрать и передать',
                'code' => 'pickup_and_drop',
                'base_price' => 59,
            ],
            [
                'name' => 'Документы и госуслуги',
                'code' => 'document_service',
                'base_price' => 99,
            ],
            [
                'name' => 'Аптека / лекарства',
                'code' => 'pharmacy',
                'base_price' => 89,
            ],
            [
                'name' => 'Особые поручения',
                'code' => 'special_errand',
                'base_price' => 149,
            ],
        ];

        foreach ($categories as $catData) {
            ServiceType::updateOrCreate(
                [
                    'code' => $catData['code'],
                    'service_category_id' => $category->id,
                ],
                [
                    'name' => $catData['name'],
                    'slug' => Str::slug($catData['name']),
                    'description' => "Категория поручений: {$catData['name']}",
                    'category' => 'errands',
                    'is_active' => true,
                    'sort_order' => 0,
                    'default_pricing' => [
                        'base_price' => $catData['base_price'],
                    ],
                ]
            );

            $this->command->info("  ✓ Created/Updated errand category: {$catData['name']} ({$catData['code']})");
        }

        $this->command->info('✅ Errand Categories seeded successfully!');
    }
}
