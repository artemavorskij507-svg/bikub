<?php

namespace Database\Seeders;

use App\Models\PricingRule;
use Illuminate\Database\Seeder;

class ErrandPricingRulesSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding Errand Pricing Rules...');

        $rules = [
            [
                'name' => 'Errand Base Fee',
                'slug' => 'errand-base-fee',
                'type' => 'base_fee',
                'value' => 49,
                'applies_to' => ['categories' => ['errands']],
                'description' => 'Базовая плата за поручение.',
            ],
            [
                'name' => 'Urgent Task',
                'slug' => 'errand-urgent-multiplier',
                'type' => 'percentage',
                'value' => 25,
                'applies_to' => ['categories' => ['errands']],
                'conditions' => ['flags' => ['is_urgent' => true]],
                'description' => 'Доплата за срочное выполнение.',
            ],
            [
                'name' => 'Evening Task',
                'slug' => 'errand-evening-multiplier',
                'type' => 'time_multiplier',
                'value' => 15,
                'applies_to' => ['categories' => ['errands']],
                'conditions' => ['hours' => [18, 22]],
                'description' => 'Доплата за выполнение вечером.',
            ],
            [
                'name' => 'Pharmacy Handling Fee',
                'slug' => 'pharmacy-handling-fee',
                'type' => 'flat',
                'value' => 39,
                'applies_to' => ['categories' => ['pharmacy']],
                'description' => 'Фиксированная плата за поручения в аптеках.',
            ],
        ];

        foreach ($rules as $rule) {
            PricingRule::updateOrCreate(
                ['slug' => $rule['slug']],
                [
                    'name' => $rule['name'],
                    'type' => $rule['type'],
                    'value' => $rule['value'],
                    'currency' => 'NOK',
                    'base_price' => $rule['type'] === 'base_fee' ? $rule['value'] : 0,
                    'applies_to' => $rule['applies_to'],
                    'conditions' => $rule['conditions'] ?? null,
                    'meta' => $rule['meta'] ?? null,
                    'description' => $rule['description'] ?? null,
                    'priority' => $rule['priority'] ?? 100,
                    'active' => true,
                ]
            );

            $this->command->info("  ✓ Rule synced: {$rule['name']}");
        }

        $this->command->info('✅ Errand Pricing Rules seeded successfully!');
    }
}
