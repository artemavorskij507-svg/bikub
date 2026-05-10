<?php

namespace Database\Seeders;

use App\Models\GeoZone;
use App\Services\Geo\GeoZoneService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class GeoZonesNarvikSeeder extends Seeder
{
    protected string $sourceFile = '/mnt/data/Доставка.txt';

    protected string $logPath;

    protected $logHandle;

    public function run(): void
    {
        $this->command->info('Seeding Narvik Geo Zones...');

        $timestamp = now()->format('Ymd_His');
        $this->logPath = storage_path("logs/geo_seed_{$timestamp}.log");
        $reportPath = storage_path("reports/geo_import_{$timestamp}.csv");

        if (! File::exists(dirname($this->logPath))) {
            File::makeDirectory(dirname($this->logPath), 0755, true);
        }
        if (! File::exists(dirname($reportPath))) {
            File::makeDirectory(dirname($reportPath), 0755, true);
        }

        $this->logHandle = fopen($this->logPath, 'w');
        $reportHandle = fopen($reportPath, 'w');
        fputcsv($reportHandle, ['zone_name', 'slug', 'type', 'status', 'source_file']);

        // Zone 1: Narvik City (polygon approximate)
        $narvikCity = GeoZone::updateOrCreate(
            ['slug' => 'narvik-city'],
            [
                'name' => 'Narvik City',
                'type' => 'polygon',
                'geometry' => [
                    'type' => 'Polygon',
                    'coordinates' => [[
                        [17.4000, 68.4300], // SW
                        [17.4500, 68.4300], // SE
                        [17.4500, 68.4450], // NE
                        [17.4000, 68.4450], // NW
                        [17.4000, 68.4300], // Close polygon
                    ]],
                ],
                'meta' => [
                    'pricing_group' => 'city_center',
                    'max_distance_km' => 30,
                ],
                'is_active' => true,
                'priority' => 10,
                'description' => 'Центральная зона Нарвика',
                'source_file' => $this->sourceFile,
            ]
        );
        $this->log('Created/Updated: Narvik City (polygon)');
        fputcsv($reportHandle, ['Narvik City', 'narvik-city', 'polygon', 'created', $this->sourceFile]);

        // Zone 2: Ankenes (circle)
        $ankenes = GeoZone::updateOrCreate(
            ['slug' => 'ankenes'],
            [
                'name' => 'Ankenes',
                'type' => 'circle',
                'geometry' => [
                    'center' => [68.42010, 17.36520],
                    'radius_m' => 15000,
                ],
                'meta' => [
                    'pricing_group' => 'ankenes',
                ],
                'is_active' => true,
                'priority' => 20,
                'description' => 'Зона Ankenes (15 км радиус)',
                'source_file' => $this->sourceFile,
                'center_latitude' => 68.42010,
                'center_longitude' => 17.36520,
                'radius_meters' => 15000,
            ]
        );
        $this->log('Created/Updated: Ankenes (circle, 15km)');
        fputcsv($reportHandle, ['Ankenes', 'ankenes', 'circle', 'created', $this->sourceFile]);

        // Zone 3: Bjerkvik (circle)
        $bjerkvik = GeoZone::updateOrCreate(
            ['slug' => 'bjerkvik'],
            [
                'name' => 'Bjerkvik',
                'type' => 'circle',
                'geometry' => [
                    'center' => [68.48000, 17.73000],
                    'radius_m' => 25000,
                ],
                'meta' => [
                    'pricing_group' => 'bjerkvik',
                ],
                'is_active' => true,
                'priority' => 30,
                'description' => 'Зона Bjerkvik (25 км радиус)',
                'source_file' => $this->sourceFile,
                'center_latitude' => 68.48000,
                'center_longitude' => 17.73000,
                'radius_meters' => 25000,
            ]
        );
        $this->log('Created/Updated: Bjerkvik (circle, 25km)');
        fputcsv($reportHandle, ['Bjerkvik', 'bjerkvik', 'circle', 'created', $this->sourceFile]);

        // Zone 4: LongRange (Narvik +60km)
        $longRange = GeoZone::updateOrCreate(
            ['slug' => 'narvik-60km'],
            [
                'name' => 'Narvik +60 km',
                'type' => 'circle',
                'geometry' => [
                    'center' => [68.43886, 17.42754],
                    'radius_m' => 60000,
                ],
                'meta' => [
                    'pricing_group' => 'narvik_60km',
                    'max_distance_km' => 60,
                ],
                'is_active' => true,
                'priority' => 100,
                'description' => 'Расширенная зона покрытия Нарвик +60 км',
                'source_file' => $this->sourceFile,
                'center_latitude' => 68.43886,
                'center_longitude' => 17.42754,
                'radius_meters' => 60000,
            ]
        );
        $this->log('Created/Updated: Narvik +60km (circle, 60km)');
        fputcsv($reportHandle, ['Narvik +60 km', 'narvik-60km', 'circle', 'created', $this->sourceFile]);

        // Try to import hints from source file if exists
        if (File::exists($this->sourceFile)) {
            $this->command->info("  Reading hints from: {$this->sourceFile}");
            $this->importHintsFromFile($this->sourceFile, $reportHandle);
        } else {
            $this->command->warn("  Source file not found: {$this->sourceFile}");
            $this->log("Source file not found: {$this->sourceFile}");
        }

        // Refresh cache
        app(GeoZoneService::class)->refreshCache();

        fclose($this->logHandle);
        fclose($reportHandle);

        $this->command->info('✅ Narvik Geo Zones seeded successfully!');
        $this->command->info("  Log: {$this->logPath}");
        $this->command->info("  Report: {$reportPath}");
    }

    protected function importHintsFromFile(string $filePath, $reportHandle): void
    {
        $content = File::get($filePath);
        $lines = explode("\n", $content);

        $zonesFound = 0;
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || str_starts_with($line, '#')) {
                continue;
            }

            // Look for zone-like patterns (e.g., "Narvik", "Ankenes", "Bjerkvik", coordinates)
            if (preg_match('/\b(narvik|ankenes|bjerkvik|68\.\d+|17\.\d+)\b/i', $line, $matches)) {
                $zonesFound++;
                $this->log('Found hint in source file: '.Str::limit($line, 100));
            }
        }

        if ($zonesFound > 0) {
            $this->log("Imported {$zonesFound} hints from source file");
            fputcsv($reportHandle, ['Source hints', '', '', 'imported', $filePath]);
        }
    }

    protected function log(string $message): void
    {
        $timestamp = now()->format('Y-m-d H:i:s');
        fwrite($this->logHandle, "[{$timestamp}] {$message}\n");
        $this->command->info("  ✓ {$message}");
    }
}
