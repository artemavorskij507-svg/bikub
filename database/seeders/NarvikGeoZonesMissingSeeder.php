<?php

namespace Database\Seeders;

use App\Models\GeoZone;
use App\Models\GeoZoneRule;
use Illuminate\Database\Seeder;

class NarvikGeoZonesMissingSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding missing Narvik geo zones...');

        $zones = [
            [
                'slug' => 'narvik-sentrum',
                'name' => 'Narvik Sentrum',
                'type' => 'city',
                'center' => [68.4384, 17.4272],
                'radius' => 2000,
                'priority' => 10,
            ],
            [
                'slug' => 'ankenes',
                'name' => 'Ankenes',
                'type' => 'city',
                'center' => [68.4035, 17.4300],
                'radius' => 6000,
                'priority' => 15,
            ],
            [
                'slug' => 'bjerkvik',
                'name' => 'Bjerkvik',
                'type' => 'city',
                'center' => [68.4796, 17.5920],
                'radius' => 8000,
                'priority' => 18,
            ],
            [
                'slug' => 'framnes',
                'name' => 'Framnes',
                'type' => 'city',
                'center' => [68.4350, 17.4200],
                'radius' => 3000,
                'priority' => 20,
            ],
            [
                'slug' => 'oyjord',
                'name' => 'Øyjord',
                'type' => 'city',
                'center' => [68.4700, 17.3500],
                'radius' => 12000,
                'priority' => 30,
            ],
            [
                'slug' => 'ballangen',
                'name' => 'Ballangen',
                'type' => 'city',
                'center' => [68.1970, 17.5250],
                'radius' => 20000,
                'priority' => 40,
            ],
        ];

        foreach ($zones as $z) {
            $geo = GeoZone::updateOrCreate(
                ['slug' => $z['slug']],
                [
                    'name' => $z['name'],
                    'type' => $z['type'] ?? 'circle',
                    'geometry' => [
                        'center' => $z['center'],
                        'radius_m' => $z['radius'],
                    ],
                    'center_latitude' => $z['center'][0],
                    'center_longitude' => $z['center'][1],
                    'radius_meters' => $z['radius'],
                    'is_active' => true,
                    'priority' => $z['priority'],
                    'description' => $z['name'].' (approx)',
                ]
            );

            // Add basic rules (timezone, currency)
            GeoZoneRule::updateOrCreate(
                ['geo_zone_id' => $geo->id, 'key' => 'timezone'],
                ['value' => ['tz' => 'Europe/Oslo'], 'description' => 'Timezone for zone', 'active' => true]
            );

            GeoZoneRule::updateOrCreate(
                ['geo_zone_id' => $geo->id, 'key' => 'currency'],
                ['value' => ['code' => 'NOK'], 'description' => 'Currency for zone', 'active' => true]
            );

            $this->command->info("  ✓ {$z['name']} (slug: {$z['slug']}) created/updated");
        }

        $this->command->info('✅ Missing Narvik geo zones seeded.');
    }
}
