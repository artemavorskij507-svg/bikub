<?php

namespace Database\Seeders;

use App\Models\Moving\MovingItem;
use App\Models\Moving\MovingOrder;
use Illuminate\Database\Seeder;

class MovingOrderItemsSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Linking moving items to orders...');

        // Заказ 1: Narvik → Fagerneset
        $order1 = MovingOrder::where('metadata->slug', 'narvik-fagerneset')->first();
        if ($order1) {
            $this->attachItemsToOrder($order1, [
                ['name' => 'Диван 3-местный', 'quantity' => 1],
                ['name' => 'Коробка большая (60×40×60)', 'quantity' => 5],
                ['name' => 'Коробка средняя (40×40×50)', 'quantity' => 7],
                ['name' => 'Стиральная машина', 'quantity' => 1],
            ]);
        }

        // Заказ 2: Ankenes → Narvik
        $order2 = MovingOrder::where('metadata->slug', 'ankenes-narvik')->first();
        if ($order2) {
            $this->attachItemsToOrder($order2, [
                ['name' => 'Кровать двуспальная', 'quantity' => 1],
                ['name' => 'Стол обеденный', 'quantity' => 1],
                ['name' => 'Холодильник высокий', 'quantity' => 1],
                ['name' => 'Коробка большая (60×40×60)', 'quantity' => 8],
                ['name' => 'Коробка средняя (40×40×50)', 'quantity' => 12],
            ]);
        }

        // Заказ 3: Bjerkvik → Narvik (офис)
        $order3 = MovingOrder::where('metadata->slug', 'bjerkvik-narvik-office')->first();
        if ($order3) {
            $this->attachItemsToOrder($order3, [
                ['name' => 'Стол обеденный', 'quantity' => 2],
                ['name' => 'Коробка большая (60×40×60)', 'quantity' => 10],
                ['name' => 'Коробка средняя (40×40×50)', 'quantity' => 20],
                ['name' => 'Холодильник высокий', 'quantity' => 1],
            ]);
        }

        $this->command->info('Moving items linked to orders.');
    }

    protected function attachItemsToOrder(MovingOrder $order, array $items): void
    {
        // Каталог предметов (шаблоны)
        $catalog = [
            'Диван 3-местный' => ['category' => 'Мебель', 'volume' => 1.8, 'weight' => 65, 'requires_assembly' => false, 'is_fragile' => false],
            'Стол обеденный' => ['category' => 'Мебель', 'volume' => 0.9, 'weight' => 35, 'requires_assembly' => false, 'is_fragile' => false],
            'Кровать двуспальная' => ['category' => 'Мебель', 'volume' => 1.5, 'weight' => 60, 'requires_assembly' => true, 'is_fragile' => false],
            'Шкаф IKEA PAX (разобранный)' => ['category' => 'Мебель (разборная)', 'volume' => 0.6, 'weight' => 50, 'requires_assembly' => true, 'is_fragile' => false],
            'Стиральная машина' => ['category' => 'Бытовая техника', 'volume' => 0.6, 'weight' => 75, 'requires_assembly' => false, 'is_fragile' => true],
            'Холодильник высокий' => ['category' => 'Бытовая техника', 'volume' => 1.2, 'weight' => 70, 'requires_assembly' => false, 'is_fragile' => true],
            'Коробка средняя (40×40×50)' => ['category' => 'Коробки', 'volume' => 0.08, 'weight' => 10, 'requires_assembly' => false, 'is_fragile' => false],
            'Коробка большая (60×40×60)' => ['category' => 'Коробки', 'volume' => 0.14, 'weight' => 15, 'requires_assembly' => false, 'is_fragile' => false],
        ];

        foreach ($items as $index => $itemData) {
            if (! isset($catalog[$itemData['name']])) {
                $this->command->warn("Catalog item '{$itemData['name']}' not found in catalog.");

                continue;
            }

            $template = $catalog[$itemData['name']];

            MovingItem::updateOrCreate(
                [
                    'moving_order_id' => $order->id,
                    'name' => $itemData['name'],
                    'sort_order' => $index + 1,
                ],
                [
                    'category' => $template['category'],
                    'volume' => $template['volume'],
                    'weight' => $template['weight'],
                    'requires_assembly' => $template['requires_assembly'],
                    'is_fragile' => $template['is_fragile'],
                    'quantity' => $itemData['quantity'],
                    'notes' => null,
                ]
            );
        }

        // Пересчитать общий объем и вес
        $order->refresh();
        $order->total_volume = $order->calculateTotalVolume();
        $order->total_weight = $order->calculateTotalWeight();
        $order->save();
    }
}
