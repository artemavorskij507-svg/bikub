<?php

namespace Database\Seeders;

use App\Models\GeoZone;
use App\Models\PricingRule;
use Illuminate\Database\Seeder;

class EcoPricingRulesNarvikSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding Narvik Eco Pricing Rules...');

        // Get Narvik zone
        $narvikZone = GeoZone::where('slug', 'narvik-center')
            ->orWhere('name', 'like', '%Narvik%')
            ->first();

        if (! $narvikZone) {
            $narvikZone = GeoZone::first();
        }

        $rules = [
            [
                'name' => 'Base eco disposal fee',
                'type' => 'eco_base_fee',
                'value' => 149,
                'currency' => 'NOK',
                'service_type' => 'eco_disposal',
            ],
            [
                'name' => 'Heavy item eco surcharge',
                'type' => 'eco_heavy_item',
                'value' => 45,
                'currency' => 'NOK',
                'service_type' => 'eco_disposal',
            ],
            [
                'name' => 'Electronic waste fee',
                'type' => 'electronics',
                'value' => 99,
                'currency' => 'NOK',
                'service_type' => 'eco_disposal',
            ],
            [
                'name' => 'Refrigerator disposal',
                'type' => 'fridge',
                'value' => 199,
                'currency' => 'NOK',
                'service_type' => 'eco_disposal',
            ],
        ];

        foreach ($rules as $ruleData) {
            PricingRule::updateOrCreate(
                [
                    'name' => $ruleData['name'],
                    'geo_zone_id' => $narvikZone->id,
                ],
                [
                    'service_type' => $ruleData['service_type'],
                    'description' => "Eco disposal pricing rule: {$ruleData['name']}",
                    'geo_zone_id' => $narvikZone->id,
                    'base_fee' => $ruleData['type'] === 'eco_base_fee' ? $ruleData['value'] : null,
                    'base_price' => $ruleData['type'] === 'eco_base_fee' ? $ruleData['value'] : 0,
                    'per_kg_fee' => $ruleData['type'] === 'eco_heavy_item' ? $ruleData['value'] : null,
                    'currency' => $ruleData['currency'],
                    'is_active' => true,
                    'conditions' => [
                        'type' => $ruleData['type'],
                        'value' => $ruleData['value'],
                        'category' => $ruleData['type'] === 'electronics' ? 'electronics' : ($ruleData['type'] === 'fridge' ? 'fridge' : null),
                    ],
                ]
            );

            $this->command->info("  ✓ Created/Updated pricing rule: {$ruleData['name']}");
        }

        $this->command->info('✅ Narvik Eco Pricing Rules seeded successfully!');
    }
}
