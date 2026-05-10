<?php

namespace Database\Seeders;

use App\Models\RoadsidePreset;
use Illuminate\Database\Seeder;

class RoadsideJobTypesSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding Roadside Job Types...');

        $jobTypes = [
            [
                'name' => 'Эвакуация',
                'code' => 'tow',
                'service_type' => 'roadside_assistance',
                'base_price' => 599.00,
                'requires_partner' => true,
            ],
            [
                'name' => 'Запуск двигателя (прикурить)',
                'code' => 'jump_start',
                'service_type' => 'roadside_assistance',
                'base_price' => 299.00,
                'requires_partner' => false,
            ],
            [
                'name' => 'Замена колеса',
                'code' => 'tire_change',
                'service_type' => 'roadside_assistance',
                'base_price' => 249.00,
                'requires_partner' => false,
            ],
            [
                'name' => 'Доставка топлива',
                'code' => 'fuel_delivery',
                'service_type' => 'roadside_assistance',
                'base_price' => 199.00,
                'requires_partner' => false,
            ],
            [
                'name' => 'Вскрытие автомобиля',
                'code' => 'unlock',
                'service_type' => 'roadside_assistance',
                'base_price' => 349.00,
                'requires_partner' => false,
            ],
            [
                'name' => 'Техническая диагностика',
                'code' => 'diagnostics',
                'service_type' => 'vehicle_inspection',
                'base_price' => 499.00,
                'requires_partner' => true,
            ],
        ];

        foreach ($jobTypes as $jobType) {
            RoadsidePreset::updateOrCreate(
                ['code' => $jobType['code']],
                [
                    'label' => $jobType['name'],
                    'description' => "Услуга: {$jobType['name']}",
                    'service_type' => $jobType['service_type'],
                    'base_price' => $jobType['base_price'],
                    'requires_partner' => $jobType['requires_partner'],
                    'is_active' => true,
                    'sort_order' => 0,
                    'metadata' => [
                        'source' => 'roadside-job-types-seeder',
                    ],
                ]
            );

            $this->command->info("  ✓ Created/Updated job type: {$jobType['name']} ({$jobType['code']})");
        }

        $this->command->info('✅ Roadside Job Types seeded successfully!');
    }
}
