<?php

namespace Database\Seeders;

use App\Models\GeoZone;
use App\Models\Partner;
use App\Models\ServiceType;
use Illuminate\Database\Seeder;

class NarvikPartnersSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding Narvik partners (stores)...');

        $partners = [
            [
                'slug' => 'rema-1000-narvik',
                'name' => 'REMA 1000 Narvik',
                'address' => 'Storgata 1, 8514 Narvik',
                'lat' => 68.4382,
                'lng' => 17.4270,
                'type' => 'retail',
                'category' => 'food',
            ],
            [
                'slug' => 'coop-extra-narvik',
                'name' => 'Coop Extra Narvik',
                'address' => 'Kongens gate 2, 8514 Narvik',
                'lat' => 68.4385,
                'lng' => 17.4275,
                'type' => 'retail',
                'category' => 'food',
            ],
            [
                'slug' => 'kiwi-narvik',
                'name' => 'Kiwi Narvik',
                'address' => 'Sjøgata 10, 8514 Narvik',
                'lat' => 68.4390,
                'lng' => 17.4250,
                'type' => 'retail',
                'category' => 'food',
            ],
            [
                'slug' => 'elkjop-narvik',
                'name' => 'Elkjøp Narvik',
                'address' => 'Industriveien 5, 8517 Narvik',
                'lat' => 68.4500,
                'lng' => 17.4800,
                'type' => 'electronics',
                'category' => 'electronics',
            ],
            [
                'slug' => 'jula-narvik',
                'name' => 'Jula Narvik',
                'address' => 'Handelsveien 3, 8517 Narvik',
                'lat' => 68.4510,
                'lng' => 17.4820,
                'type' => 'hardware',
                'category' => 'home',
            ],
            [
                'slug' => 'biltema-narvik',
                'name' => 'Biltema Narvik',
                'address' => 'Verkstedveien 2, 8517 Narvik',
                'lat' => 68.4520,
                'lng' => 17.4850,
                'type' => 'auto',
                'category' => 'auto',
            ],
        ];

        // Find a default Narvik zone id to attach partners to
        $narvikZone = GeoZone::where('slug', 'narvik-sentrum')->first();

        foreach ($partners as $p) {
            $partner = Partner::updateOrCreate(
                ['slug' => $p['slug']],
                [
                    'name' => $p['name'],
                    'type' => $p['type'],
                    'address' => $p['address'],
                    'latitude' => $p['lat'],
                    'longitude' => $p['lng'],
                    'opening_hours' => json_encode([
                        'mon_fri' => '08:00-20:00',
                        'sat' => '09:00-18:00',
                        'sun' => '10:00-16:00',
                    ]),
                    'is_active' => true,
                    'metadata' => ['category' => $p['category']],
                ]
            );

            // attach partner to Narvik zone if present
            if ($narvikZone) {
                $partner->zones()->syncWithoutDetaching([$narvikZone->id]);
            }

            // Attach a few relevant service types (delivery/parcel)
            $serviceSlugs = ['grocery-delivery', 'parcel-delivery', 'pharmacy-delivery'];
            $serviceIds = ServiceType::whereIn('slug', $serviceSlugs)->pluck('id')->toArray();

            foreach ($serviceIds as $sid) {
                $partner->services()->syncWithoutDetaching([$sid => ['is_active' => true, 'base_fee_cents' => 0, 'per_km_cents' => 0]]);
            }

            $this->command->info("  ✓ Partner {$partner->name} upserted");
        }

        $this->command->info('✅ Narvik partners seeded.');
    }
}
