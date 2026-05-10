<?php

namespace Database\Seeders;

use App\Models\GeoZone;
use App\Models\PricingRule;
use App\Models\ServiceType;
use Illuminate\Database\Seeder;

class NarvikPricingSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding Narvik pricing rules and multipliers...');

        $zone = GeoZone::where('slug', 'narvik-sentrum')->first();
        $zone60 = GeoZone::where('slug', 'narvik-60km')->first();

        $rules = [
            // Grocery/delivery base
            [
                'slug' => 'grocery-delivery-base',
                'service_slug' => 'grocery-delivery',
                'base_price' => 59.00,
                'unit' => 'job',
                'per_km_fee' => 8.00,
                'currency' => 'NOK',
            ],
            // Apartment moving per-hour
            [
                'slug' => 'apartment-moving-hourly',
                'service_slug' => 'apartment-moving',
                'base_price' => 1200.00,
                'unit' => 'hour',
                'per_km_fee' => 10.00,
                'currency' => 'NOK',
            ],
            // Plumbing (handyman) per-hour
            [
                'slug' => 'plumbing-hourly',
                'service_slug' => 'plumbing',
                'base_price' => 699.00,
                'unit' => 'hour',
                'per_km_fee' => 0.00,
                'currency' => 'NOK',
            ],
            // Car towing base
            [
                'slug' => 'car-towing-base',
                'service_slug' => 'car-towing',
                'base_price' => 1500.00,
                'unit' => 'job',
                'per_km_fee' => 20.00,
                'currency' => 'NOK',
            ],
        ];

        foreach ($rules as $r) {
            $stype = ServiceType::where('slug', $r['service_slug'])->first();
            if (! $stype) {
                $slugName = $r['slug'] ?? ($r['service_slug'].'-base');
                $this->command->warn("ServiceType {$r['service_slug']} not found, skipping pricing rule {$slugName}");

                continue;
            }

            $pr = PricingRule::updateOrCreate(
                ['slug' => $r['slug'] ?? $r['service_slug'].'-base'],
                [
                    'service_type_id' => $stype->id,
                    'name' => $r['service_slug'].' base price',
                    'description' => 'Auto-seeded base pricing for '.$r['service_slug'],
                    'base_price' => $r['base_price'],
                    'unit' => $r['unit'],
                    'per_km_fee' => $r['per_km_fee'],
                    'currency' => $r['currency'],
                    'geo_zone_id' => $zone?->id,
                    'applies_to' => json_encode(['zones' => [$zone?->slug]]),
                    'type' => 'base',
                    'meta' => json_encode(['source' => 'narvik-seeder']),
                ]
            );

            $this->command->info("  ✓ PricingRule {$pr->slug} upserted");
        }

        // Multipliers: weekend multiplier and outer-zone multiplier
        $mults = [
            ['key' => 'narvik-weekend-multiplier', 'factor' => 1.2, 'description' => 'Weekend multiplier for Narvik services', 'applies_to' => json_encode(['zones' => ['narvik-sentrum']])],
            ['key' => 'narvik-outer-zone-multiplier', 'factor' => 1.15, 'description' => 'Outer zone surcharge', 'applies_to' => json_encode(['zones' => ['narvik-60km']])],
        ];

        foreach ($mults as $m) {
            $pr = PricingRule::updateOrCreate(
                ['slug' => $m['key']],
                [
                    'service_type_id' => null,
                    'name' => $m['key'],
                    'description' => $m['description'],
                    'base_price' => 0,
                    'unit' => 'multiplier',
                    'per_km_fee' => 0,
                    'currency' => 'NOK',
                    'geo_zone_id' => $zone60?->id,
                    'applies_to' => $m['applies_to'],
                    'type' => 'multiplier',
                    'value' => $m['factor'],
                    'meta' => json_encode(['description' => $m['description']]),
                ]
            );

            $this->command->info("  ✓ Multiplier {$pr->slug} upserted (factor={$m['factor']})");
        }

        $this->command->info('✅ Narvik pricing seeded.');
    }
}
