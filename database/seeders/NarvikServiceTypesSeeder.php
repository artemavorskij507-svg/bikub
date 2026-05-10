<?php

namespace Database\Seeders;

use App\Models\GeoZone;
use App\Models\PricingRule;
use App\Models\ServiceCategory;
use App\Models\ServiceType;
use Illuminate\Database\Seeder;

class NarvikServiceTypesSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding Narvik service types...');

        $geoNarvik = GeoZone::where('slug', 'narvik-city')->first();

        $types = [
            // Delivery
            ['slug' => 'grocery-delivery', 'name' => ['en' => 'Grocery delivery', 'nb' => 'Dagligvarelevering'], 'category' => 'delivery', 'icon' => 'shopping-basket', 'base_price' => 59.00, 'unit' => 'job'],
            ['slug' => 'pharmacy-delivery', 'name' => ['en' => 'Pharmacy delivery', 'nb' => 'Apoteklevering'], 'category' => 'delivery', 'icon' => 'prescription-bottle', 'base_price' => 79.00, 'unit' => 'job'],
            ['slug' => 'parcel-delivery', 'name' => ['en' => 'Parcel delivery', 'nb' => 'Pakkelevering'], 'category' => 'delivery', 'icon' => 'box', 'base_price' => 49.00, 'unit' => 'job'],

            // Moving
            ['slug' => 'apartment-moving', 'name' => ['en' => 'Apartment moving', 'nb' => 'Leilighetsflytting'], 'category' => 'moving', 'icon' => 'people-carry-box', 'base_price' => 1200.00, 'unit' => 'job'],
            ['slug' => 'office-moving', 'name' => ['en' => 'Office moving', 'nb' => 'Kontorflytting'], 'category' => 'moving', 'icon' => 'building', 'base_price' => 2500.00, 'unit' => 'job'],
            ['slug' => 'heavy-items', 'name' => ['en' => 'Heavy items', 'nb' => 'Tunge gjenstander'], 'category' => 'moving', 'icon' => 'boxes', 'base_price' => 600.00, 'unit' => 'job'],

            // Handyman
            ['slug' => 'plumbing', 'name' => ['en' => 'Plumbing', 'nb' => 'VVS-tjenester'], 'category' => 'handyman', 'icon' => 'wrench', 'base_price' => 650.00, 'unit' => 'hour'],
            ['slug' => 'electrical', 'name' => ['en' => 'Electrical', 'nb' => 'Elektriker'], 'category' => 'handyman', 'icon' => 'bolt', 'base_price' => 700.00, 'unit' => 'hour'],
            ['slug' => 'furniture-assembly', 'name' => ['en' => 'Furniture assembly', 'nb' => 'Møbelmontering'], 'category' => 'handyman', 'icon' => 'cubes', 'base_price' => 450.00, 'unit' => 'hour'],

            // Eco
            ['slug' => 'furniture-disposal', 'name' => ['en' => 'Furniture disposal', 'nb' => 'Møbeloppsamling'], 'category' => 'eco', 'icon' => 'trash', 'base_price' => 499.00, 'unit' => 'job'],
            ['slug' => 'electronic-waste', 'name' => ['en' => 'Electronic waste', 'nb' => 'El-avfall'], 'category' => 'eco', 'icon' => 'microchip', 'base_price' => 399.00, 'unit' => 'job'],
            ['slug' => 'recycling-pickup', 'name' => ['en' => 'Recycling pickup', 'nb' => 'Resirkulering'], 'category' => 'eco', 'icon' => 'recycle', 'base_price' => 299.00, 'unit' => 'job'],

            // Tow
            ['slug' => 'car-towing', 'name' => ['en' => 'Car towing', 'nb' => 'Bilberging'], 'category' => 'tow', 'icon' => 'tow-truck', 'base_price' => 990.00, 'unit' => 'job'],
            ['slug' => 'roadside-assistance', 'name' => ['en' => 'Roadside assistance', 'nb' => 'Veihjelp'], 'category' => 'tow', 'icon' => 'life-ring', 'base_price' => 450.00, 'unit' => 'job'],
            ['slug' => 'battery-boost', 'name' => ['en' => 'Battery boost', 'nb' => 'Batterihjelp'], 'category' => 'tow', 'icon' => 'battery-full', 'base_price' => 350.00, 'unit' => 'job'],
        ];

        foreach ($types as $index => $t) {
            $category = ServiceCategory::where('slug', $t['category'])->first();

            $st = ServiceType::updateOrCreate(
                ['slug' => $t['slug']],
                [
                    'name' => $t['name']['en'],
                    'description' => $t['name']['en'],
                    'icon' => $t['icon'],
                    'category' => $t['category'],
                    'service_category_id' => $category?->id,
                    'features' => ['availability' => ['narvik-city']],
                    'is_active' => true,
                    'sort_order' => ($index + 1) * 10,
                ]
            );

            // Create pricing rule for base price (applies to Narvik city if available)
            $ruleSlug = $t['slug'].'-base-price';

            $pricingAttrs = [
                'service_type_id' => $st->id,
                'name' => $t['name']['en'].' — Base price',
                'slug' => $ruleSlug,
                'type' => 'base',
                'base_price' => $t['base_price'],
                'unit' => $t['unit'],
                'currency' => 'NOK',
                'is_active' => true,
                'active' => true,
            ];

            if ($geoNarvik) {
                $pricingAttrs['geo_zone_id'] = $geoNarvik->id;
                $pricingAttrs['applies_to'] = ['zones' => [$geoNarvik->slug]];
            }

            PricingRule::updateOrCreate(
                ['slug' => $ruleSlug],
                $pricingAttrs
            );

            $this->command->info("  ✓ ServiceType: {$t['slug']} seeded/updated (pricing rule created)");
        }

        $this->command->info('✅ Narvik service types seeded.');
    }
}
