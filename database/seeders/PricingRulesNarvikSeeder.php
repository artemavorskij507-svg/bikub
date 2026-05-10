<?php

namespace Database\Seeders;

use App\Models\PricingRule;
use Illuminate\Database\Seeder;

class PricingRulesNarvikSeeder extends Seeder
{
    public function run(): void
    {
        $rules = collect([
            [
                'name' => 'Errand Base Fee',
                'slug' => 'errand-base-fee',
                'type' => 'base_fee',
                'value' => 49,
                'currency' => 'NOK',
                'applies_to' => [
                    'categories' => ['errands'],
                    'service_types' => ['errand'],
                ],
                'description' => 'Базовая стоимость поручения в зоне Нарвика.',
            ],
            [
                'name' => 'Urgent Task',
                'slug' => 'errand-urgent-multiplier',
                'type' => 'percentage',
                'value' => 25,
                'currency' => 'NOK',
                'applies_to' => [
                    'categories' => ['errands'],
                ],
                'conditions' => [
                    'flags' => ['is_urgent' => true],
                ],
                'meta' => ['label' => 'Urgent multiplier'],
                'description' => 'Доплата 25% для срочных поручений.',
            ],
            [
                'name' => 'Evening Task',
                'slug' => 'errand-evening-multiplier',
                'type' => 'time_multiplier',
                'value' => 15,
                'currency' => 'NOK',
                'applies_to' => [
                    'categories' => ['errands'],
                ],
                'conditions' => [
                    'hours' => [18, 22],
                ],
                'meta' => ['label' => 'Evening window'],
                'description' => 'Доплата 15% за работы после 18:00.',
            ],
            [
                'name' => 'Pharmacy Handling Fee',
                'slug' => 'pharmacy-handling-fee',
                'type' => 'flat',
                'value' => 39,
                'currency' => 'NOK',
                'applies_to' => [
                    'categories' => ['pharmacy'],
                ],
                'description' => 'Фиксированная доплата за работу с рецептами и препаратами.',
            ],
        ]);

        $rules->each(function (array $rule) {
            PricingRule::updateOrCreate(
                ['slug' => $rule['slug']],
                [
                    'name' => $rule['name'],
                    'type' => $rule['type'],
                    'value' => $rule['value'],
                    'currency' => $rule['currency'],
                    'base_price' => $rule['type'] === 'base_fee' ? $rule['value'] : 0,
                    'unit' => $rule['unit'] ?? null,
                    'applies_to' => $rule['applies_to'] ?? null,
                    'conditions' => $rule['conditions'] ?? null,
                    'priority' => $rule['priority'] ?? 100,
                    'active' => true,
                    'meta' => $rule['meta'] ?? null,
                    'description' => $rule['description'] ?? null,
                ]
            );
        });
    }
}
