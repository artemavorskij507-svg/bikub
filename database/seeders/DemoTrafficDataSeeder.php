<?php

namespace Database\Seeders;

use App\Models\TrafficIncident;
use App\Models\TravelTime;
use Illuminate\Database\Seeder;

class DemoTrafficDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Narvik coordinates
        $narvikLat = 68.438;
        $narvikLng = 17.427;

        // Demo traffic incidents for Narvik region
        $incidents = [
            [
                'external_id' => 'demo-incident-001',
                'title' => 'Vinterføre på E6',
                'description' => 'Vinterføre og glatt kjørebane på E6 mellom Narvik og Bjerkvik. Reduser farten.',
                'severity' => 'moderate',
                'status' => 'active',
                'starts_at' => now()->subHours(2),
                'ends_at' => now()->addHours(6),
                'lat' => 68.445,
                'lng' => 17.430,
                'geometry' => ['type' => 'Point', 'coordinates' => [17.430, 68.445]],
                'meta' => ['road' => 'E6', 'direction' => 'north'],
                'source_url' => 'demo',
            ],
            [
                'external_id' => 'demo-incident-002',
                'title' => 'Veiarbeid på Rv83',
                'description' => 'Veiarbeid på Riksveg 83 i Narvik sentrum. En fil stengt.',
                'severity' => 'low',
                'status' => 'active',
                'starts_at' => now()->subDays(1),
                'ends_at' => now()->addDays(2),
                'lat' => 68.438,
                'lng' => 17.427,
                'geometry' => ['type' => 'Point', 'coordinates' => [17.427, 68.438]],
                'meta' => ['road' => 'Rv83', 'type' => 'roadwork'],
                'source_url' => 'demo',
            ],
            [
                'external_id' => 'demo-incident-003',
                'title' => 'Snø og vinterføre i fjellet',
                'description' => 'Snøfall og vinterføre på fjellet. Bruk vinterdekk.',
                'severity' => 'high',
                'status' => 'active',
                'starts_at' => now()->subHours(12),
                'ends_at' => now()->addDays(1),
                'lat' => 68.480,
                'lng' => 17.400,
                'geometry' => ['type' => 'Point', 'coordinates' => [17.400, 68.480]],
                'meta' => ['area' => 'mountain', 'weather' => 'snow'],
                'source_url' => 'demo',
            ],
        ];

        foreach ($incidents as $incident) {
            TrafficIncident::updateOrCreate(
                ['external_id' => $incident['external_id']],
                $incident
            );
        }

        $this->command->info('Created '.count($incidents).' demo traffic incidents');

        // Demo travel times for Narvik routes
        $routes = [
            [
                'external_id' => 'demo-route-001',
                'route_name' => 'Narvik → Bjerkvik',
                'from_location' => 'Narvik sentrum',
                'to_location' => 'Bjerkvik',
                'from_lat' => 68.438,
                'from_lng' => 17.427,
                'to_lat' => 68.550,
                'to_lng' => 17.550,
                'travel_time_seconds' => 840, // 14 minutes
                'distance_meters' => 15200, // 15.2 km
                'average_speed_kmh' => 65.1,
                'status' => 'normal',
                'measured_at' => now()->subMinutes(30), // Within last 2 hours
                'geometry' => [
                    'type' => 'LineString',
                    'coordinates' => [
                        [17.427, 68.438],
                        [17.450, 68.460],
                        [17.500, 68.520],
                        [17.550, 68.550],
                    ],
                ],
                'meta' => ['road' => 'E6', 'conditions' => 'normal'],
                'source_url' => 'demo',
            ],
            [
                'external_id' => 'demo-route-002',
                'route_name' => 'Narvik → Harstad',
                'from_location' => 'Narvik',
                'to_location' => 'Harstad',
                'from_lat' => 68.438,
                'from_lng' => 17.427,
                'to_lat' => 68.798,
                'to_lng' => 16.542,
                'travel_time_seconds' => 5400, // 90 minutes
                'distance_meters' => 78500, // 78.5 km
                'average_speed_kmh' => 52.3,
                'status' => 'delayed',
                'measured_at' => now()->subMinutes(45), // Within last 2 hours
                'geometry' => [
                    'type' => 'LineString',
                    'coordinates' => [
                        [17.427, 68.438],
                        [17.350, 68.480],
                        [17.200, 68.550],
                        [17.000, 68.600],
                        [16.800, 68.650],
                        [16.600, 68.700],
                        [16.542, 68.798],
                    ],
                ],
                'meta' => ['road' => 'E10', 'conditions' => 'winter'],
                'source_url' => 'demo',
            ],
            [
                'external_id' => 'demo-route-003',
                'route_name' => 'Narvik → Ballangen',
                'from_location' => 'Narvik',
                'to_location' => 'Ballangen',
                'from_lat' => 68.438,
                'from_lng' => 17.427,
                'to_lat' => 68.342,
                'to_lng' => 16.832,
                'travel_time_seconds' => 1980, // 33 minutes
                'distance_meters' => 28500, // 28.5 km
                'average_speed_kmh' => 51.8,
                'status' => 'normal',
                'measured_at' => now()->subMinutes(15), // Within last 2 hours
                'geometry' => [
                    'type' => 'LineString',
                    'coordinates' => [
                        [17.427, 68.438],
                        [17.300, 68.410],
                        [17.100, 68.380],
                        [16.900, 68.360],
                        [16.832, 68.342],
                    ],
                ],
                'meta' => ['road' => 'Rv827', 'conditions' => 'normal'],
                'source_url' => 'demo',
            ],
        ];

        foreach ($routes as $route) {
            TravelTime::updateOrCreate(
                ['external_id' => $route['external_id']],
                $route
            );
        }

        $this->command->info('Created '.count($routes).' demo travel time routes');
    }
}
