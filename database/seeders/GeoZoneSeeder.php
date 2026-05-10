<?php

namespace Database\Seeders;

use App\Models\GeoZone;
use Illuminate\Database\Seeder;

class GeoZoneSeeder extends Seeder
{
    public function run(): void
    {
        $geoZones = [
            [
                'name' => 'Нарвік - Основна зона обслуговування',
                'slug' => 'narvik-main-service-area',
                'description' => 'Основна зона обслуговування велосипедів в Нарвіку та навколишніх районах',
                'type' => 'service_area',
                'center_latitude' => 68.4378,
                'center_longitude' => 17.4279,
                'radius_meters' => 60000,
                'polygon_coordinates' => null,
                'is_active' => true,
                'metadata' => [
                    'city' => 'Narvik',
                    'country' => 'Norway',
                    'region' => 'Nordland',
                    'population' => 14000,
                    'service_hours' => '24/7',
                    'emergency_contact' => '+47 12345678',
                ],
            ],
            [
                'name' => 'Нарвік - Центр міста',
                'slug' => 'narvik-city-center',
                'description' => 'Центральна частина Нарвіка з високою щільністю велосипедів',
                'type' => 'service_area',
                'center_latitude' => 68.4378,
                'center_longitude' => 17.4279,
                'radius_meters' => 5000,
                'polygon_coordinates' => null,
                'is_active' => true,
                'metadata' => [
                    'priority' => 'high',
                    'avg_response_time_minutes' => 15,
                    'peak_hours' => ['08:00-10:00', '17:00-19:00'],
                ],
            ],
            [
                'name' => 'Нарвік - Промислова зона',
                'slug' => 'narvik-industrial-area',
                'description' => 'Промислова зона Нарвіка з обмеженим доступом',
                'type' => 'restricted_area',
                'center_latitude' => 68.4500,
                'center_longitude' => 17.4000,
                'radius_meters' => 3000,
                'polygon_coordinates' => null,
                'is_active' => true,
                'metadata' => [
                    'access_level' => 'restricted',
                    'requires_permission' => true,
                    'security_contact' => '+47 87654321',
                ],
            ],
            [
                'name' => 'Нарвік - Залізничний вокзал',
                'slug' => 'narvik-train-station',
                'description' => 'Зона підбору та доставки біля залізничного вокзалу',
                'type' => 'pickup_point',
                'center_latitude' => 68.4350,
                'center_longitude' => 17.4300,
                'radius_meters' => 500,
                'polygon_coordinates' => null,
                'is_active' => true,
                'metadata' => [
                    'facility_type' => 'transport_hub',
                    'parking_available' => true,
                    'accessibility' => 'wheelchair_accessible',
                ],
            ],
            [
                'name' => 'Нарвік - Університет',
                'slug' => 'narvik-university',
                'description' => 'Зона обслуговування біля університету Нарвіка',
                'type' => 'service_area',
                'center_latitude' => 68.4200,
                'center_longitude' => 17.4100,
                'radius_meters' => 2000,
                'polygon_coordinates' => null,
                'is_active' => true,
                'metadata' => [
                    'facility_type' => 'educational',
                    'student_discount' => true,
                    'peak_hours' => ['12:00-14:00', '16:00-18:00'],
                ],
            ],
            [
                'name' => 'Нарвік - Гірськолижний курорт',
                'slug' => 'narvik-ski-resort',
                'description' => 'Зона обслуговування біля гірськолижного курорту',
                'type' => 'service_area',
                'center_latitude' => 68.4600,
                'center_longitude' => 17.4500,
                'radius_meters' => 3000,
                'polygon_coordinates' => null,
                'is_active' => true,
                'metadata' => [
                    'facility_type' => 'recreation',
                    'seasonal' => true,
                    'peak_season' => ['december', 'january', 'february', 'march'],
                ],
            ],
        ];

        foreach ($geoZones as $geoZone) {
            GeoZone::updateOrCreate(
                ['slug' => $geoZone['slug']],
                $geoZone
            );
        }
    }
}
