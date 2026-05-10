<?php

namespace Database\Seeders;

use App\Models\RoadsidePreset;
use Illuminate\Database\Seeder;

class RoadsideInspectionPresetsSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding Roadside Inspection Presets...');

        $presets = [
            [
                'name' => 'Базовый осмотр — эвакуация',
                'code' => 'inspection_tow',
                'fields' => ['Состояние колес', 'Течь жидкостей', 'Целостность бампера', 'Блокировка руля'],
                'service_type' => 'vehicle_inspection',
                'base_price' => 0, // Included in tow price
            ],
            [
                'name' => 'Базовый осмотр — запуск двигателя',
                'code' => 'inspection_jump_start',
                'fields' => ['Состояние АКБ', 'Температура двигателя', 'Ошибка на приборке', 'Топливо'],
                'service_type' => 'vehicle_inspection',
                'base_price' => 0, // Included in service price
            ],
        ];

        foreach ($presets as $presetData) {
            RoadsidePreset::updateOrCreate(
                ['code' => $presetData['code']],
                [
                    'label' => $presetData['name'],
                    'description' => "Пресет осмотра: {$presetData['name']}",
                    'service_type' => $presetData['service_type'],
                    'base_price' => $presetData['base_price'],
                    'requires_partner' => false,
                    'is_active' => true,
                    'sort_order' => 0,
                    'metadata' => [
                        'inspection_fields' => $presetData['fields'],
                        'source' => 'roadside-inspection-presets-seeder',
                    ],
                ]
            );

            $this->command->info("  ✓ Created/Updated inspection preset: {$presetData['name']}");
        }

        $this->command->info('✅ Roadside Inspection Presets seeded successfully!');
    }
}
