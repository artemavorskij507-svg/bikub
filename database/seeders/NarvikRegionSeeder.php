<?php

namespace Database\Seeders;

use App\Models\GeoZone;
use App\Models\GeoZoneRule;
use Illuminate\Database\Seeder;

class NarvikRegionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Seeding Narvik region (as GeoZone)...');

        // Use existing narvik-city zone if present, otherwise create/update
        $zone = GeoZone::updateOrCreate(
            ['slug' => 'narvik-city'],
            [
                'name' => 'Narvik',
                'type' => 'city',
                'center_latitude' => 68.4384,
                'center_longitude' => 17.4272,
                'meta' => array_merge((array) optional(GeoZone::where('slug', 'narvik-city')->first())->meta ?? [], [
                    'timezone' => 'Europe/Oslo',
                    'currency' => 'NOK',
                    'country' => 'Norway',
                ]),
                'is_active' => true,
                'priority' => 5,
                'description' => 'City record for Narvik (region-level metadata)',
            ]
        );

        // Ensure GeoZoneRule entries for timezone and currency exist
        GeoZoneRule::updateOrCreate(
            ['geo_zone_id' => $zone->id, 'key' => 'timezone'],
            ['value' => ['tz' => 'Europe/Oslo'], 'description' => 'Timezone for Narvik', 'active' => true]
        );

        GeoZoneRule::updateOrCreate(
            ['geo_zone_id' => $zone->id, 'key' => 'currency'],
            ['value' => ['code' => 'NOK'], 'description' => 'Default currency for Narvik', 'active' => true]
        );

        $this->command->info('✅ Narvik region seeded/updated.');
    }
}
