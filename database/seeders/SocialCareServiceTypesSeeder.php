<?php

namespace Database\Seeders;

use App\Models\CareService;
use Illuminate\Database\Seeder;

class SocialCareServiceTypesSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding Social Care Service Types...');

        $serviceTypes = [
            [
                'name' => 'Уборка и помощь по дому',
                'code' => 'home_care',
                'description' => 'Базовая домашняя помощь: уборка, готовка, небольшие поручения',
                'base_duration_minutes' => 120,
                'base_price_nok' => 299.00,
            ],
            [
                'name' => 'Покупки и сопровождение',
                'code' => 'shopping_assist',
                'description' => 'Помощь с покупками и сопровождением в магазины',
                'base_duration_minutes' => 90,
                'base_price_nok' => 249.00,
            ],
            [
                'name' => 'Медицинское сопровождение',
                'code' => 'medical_escort',
                'description' => 'Сопровождение в больницу, к врачу, в аптеку',
                'base_duration_minutes' => 180,
                'base_price_nok' => 399.00,
            ],
            [
                'name' => 'Социальное общение',
                'code' => 'social_visit',
                'description' => 'Социальные визиты, беседы, поддержка',
                'base_duration_minutes' => 60,
                'base_price_nok' => 199.00,
            ],
            [
                'name' => 'Психологическая поддержка',
                'code' => 'emotional_support',
                'description' => 'Эмоциональная поддержка и психологическая помощь',
                'base_duration_minutes' => 60,
                'base_price_nok' => 349.00,
            ],
        ];

        foreach ($serviceTypes as $serviceType) {
            CareService::updateOrCreate(
                ['code' => $serviceType['code']],
                [
                    'name' => $serviceType['name'],
                    'description' => $serviceType['description'],
                    'required_level' => 'SOCIAL_HELPER',
                    'base_duration_minutes' => $serviceType['base_duration_minutes'],
                    'base_price_nok' => $serviceType['base_price_nok'],
                    'is_recurring_available' => true,
                    'is_active' => true,
                ]
            );

            $this->command->info("  ✓ Created/Updated service type: {$serviceType['name']} ({$serviceType['code']})");
        }

        $this->command->info('✅ Social Care Service Types seeded successfully!');
    }
}
