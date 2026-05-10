<?php

namespace Database\Seeders;

use App\Models\VehicleInspectionPreset;
use Illuminate\Database\Seeder;

class VehicleInspectionPresetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Базовые пресеты для осмотра авто
        $presets = [
            [
                'title' => 'Базовый осмотр',
                'slug' => 'inspection_basic',
                'price' => 1500.00,
                'description' => 'Базовый осмотр автомобиля перед покупкой',
                'checklist' => [
                    'Проверка кузова',
                    'Проверка двигателя',
                    'Проверка ходовой',
                ],
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'title' => 'Полный осмотр',
                'slug' => 'inspection_full',
                'price' => 3000.00,
                'description' => 'Полный технический осмотр с диагностикой',
                'checklist' => [
                    'Полная диагностика',
                    'Проверка всех систем',
                    'Проверка документов',
                ],
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'title' => 'Сопровождение сделки',
                'slug' => 'inspection_service',
                'price' => 5000.00,
                'description' => 'Полное сопровождение сделки купли-продажи',
                'checklist' => [
                    'Осмотр автомобиля',
                    'Проверка документов',
                    'Сопровождение сделки',
                ],
                'is_active' => true,
                'sort_order' => 3,
            ],
        ];

        foreach ($presets as $preset) {
            VehicleInspectionPreset::updateOrCreate(
                ['slug' => $preset['slug']],
                $preset
            );
        }
    }
}
