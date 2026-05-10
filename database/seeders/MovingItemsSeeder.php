<?php

namespace Database\Seeders;

use App\Models\Moving\MovingItem;
use Illuminate\Database\Seeder;

class MovingItemsSeeder extends Seeder
{
    public function run(): void
    {
        $catalogItems = [
            [
                'name' => 'Диван 3-местный',
                'volume_m3' => 1.8,
                'weight_kg' => 65,
                'category' => 'Мебель',
                'packing_required' => true,
                'difficulty' => 'medium',
                'requires_assembly' => false,
                'is_fragile' => false,
            ],
            [
                'name' => 'Стол обеденный',
                'volume_m3' => 0.9,
                'weight_kg' => 35,
                'category' => 'Мебель',
                'packing_required' => true,
                'difficulty' => 'medium',
                'requires_assembly' => false,
                'is_fragile' => false,
            ],
            [
                'name' => 'Кровать двуспальная',
                'volume_m3' => 1.5,
                'weight_kg' => 60,
                'category' => 'Мебель',
                'packing_required' => true,
                'difficulty' => 'hard',
                'requires_assembly' => true,
                'is_fragile' => false,
            ],
            [
                'name' => 'Шкаф IKEA PAX (разобранный)',
                'volume_m3' => 0.6,
                'weight_kg' => 50,
                'category' => 'Мебель (разборная)',
                'packing_required' => false,
                'difficulty' => 'medium',
                'requires_assembly' => true,
                'is_fragile' => false,
            ],
            [
                'name' => 'Стиральная машина',
                'volume_m3' => 0.6,
                'weight_kg' => 75,
                'category' => 'Бытовая техника',
                'packing_required' => false,
                'difficulty' => 'hard',
                'requires_assembly' => false,
                'is_fragile' => true,
            ],
            [
                'name' => 'Холодильник высокий',
                'volume_m3' => 1.2,
                'weight_kg' => 70,
                'category' => 'Бытовая техника',
                'packing_required' => false,
                'difficulty' => 'hard',
                'requires_assembly' => false,
                'is_fragile' => true,
            ],
            [
                'name' => 'Коробка средняя (40×40×50)',
                'volume_m3' => 0.08,
                'weight_kg' => 10,
                'category' => 'Коробки',
                'packing_required' => false,
                'difficulty' => 'easy',
                'requires_assembly' => false,
                'is_fragile' => false,
            ],
            [
                'name' => 'Коробка большая (60×40×60)',
                'volume_m3' => 0.14,
                'weight_kg' => 15,
                'category' => 'Коробки',
                'packing_required' => false,
                'difficulty' => 'easy',
                'requires_assembly' => false,
                'is_fragile' => false,
            ],
        ];

        $this->command->info('Creating moving items catalog...');

        foreach ($catalogItems as $item) {
            MovingItem::updateOrCreate(
                [
                    'name' => $item['name'],
                    'category' => $item['category'],
                ],
                [
                    'moving_order_id' => null,
                    'volume' => $item['volume_m3'],
                    'weight' => $item['weight_kg'],
                    'requires_assembly' => $item['requires_assembly'],
                    'is_fragile' => $item['is_fragile'],
                    'quantity' => 1,
                    'notes' => sprintf(
                        'Сложность: %s. %s',
                        $item['difficulty'],
                        $item['packing_required'] ? 'Требуется упаковка.' : 'Упаковка не требуется.'
                    ),
                    'sort_order' => 0,
                ]
            );
        }

        $this->command->info('Moving items catalog created.');
    }
}
