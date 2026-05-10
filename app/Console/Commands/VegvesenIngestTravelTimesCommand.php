<?php

namespace App\Console\Commands;

use App\Services\VegvesenCkanClient;
use App\Services\VegvesenTravelTimeIngestor;
use Illuminate\Console\Command;

class VegvesenIngestTravelTimesCommand extends Command
{
    protected $signature = 'vegvesen:ingest-travel-times {--query=} {--limit=50}';

    protected $description = 'Ingest Reisetider (travel times) for a region (default Narvik)';

    public function handle(VegvesenCkanClient $ckan, VegvesenTravelTimeIngestor $ingestor): int
    {
        $query = $this->option('query') ?: (config('vegvesen.default_query').' Reisetider');
        $limit = (int) $this->option('limit');

        $this->info("Searching CKAN for travel times: {$query}");
        $packages = $ckan->search($query, $limit);

        $this->info('Found packages: '.count($packages));

        $resources = [];
        foreach ($packages as $pkg) {
            $pkgName = $pkg['name'] ?? 'unknown';
            $pkgTitle = $pkg['title'] ?? 'No title';

            // For Reisetider, get full package resources
            if ($pkgName === 'reisetider') {
                $this->info("Fetching full metadata for package: {$pkgName}");
                $fullResources = $ckan->getFullPackageResources($pkgName);
                $fullResourceCount = count($fullResources);
                $this->line("  Package: {$pkgName} - {$pkgTitle} ({$fullResourceCount} resources from full metadata)");

                foreach ($fullResources as $r) {
                    $fmt = strtoupper($r['format'] ?? 'UNKNOWN');
                    $url = $r['url'] ?? $r['download_url'] ?? $r['access_url'] ?? null;

                    if (! $url || $url === '') {
                        if ($fmt === 'XML') {
                            $url = 'https://www.vegvesen.no/trafikk/reisetider/reisetider.xml';
                        } elseif ($fmt === 'WFS') {
                            $url = 'https://www.vegvesen.no/trafikk/reisetider/reisetider.wfs';
                        } elseif ($fmt === 'WMS') {
                            continue;
                        }
                    }

                    $r['url'] = $url;
                    $this->line("    Resource: {$fmt} - {$url}");
                    $resources[] = $r;
                }
            } else {
                $pkgResources = $pkg['resources'] ?? [];
                $pkgResourceCount = count($pkgResources);
                $this->line("  Package: {$pkgName} - {$pkgTitle} ({$pkgResourceCount} resources)");
                foreach ($pkgResources as $r) {
                    $fmt = strtoupper($r['format'] ?? 'UNKNOWN');
                    $url = $ckan->extractResourceUrl($r, $pkgName) ?? $r['url'] ?? 'N/A';
                    $r['url'] = $url;
                    $this->line("    Resource: {$fmt} - {$url}");
                    $resources[] = $r;
                }
            }
        }

        $this->info('Total resources to process: '.count($resources));

        if (empty($resources)) {
            $this->warn('No resources found.');

            return self::FAILURE;
        }

        $stored = $ingestor->ingestNarvik($resources);
        $this->info("✓ Stored/updated travel times: {$stored}");

        if ($stored > 0) {
            $this->line("\nSample travel times:");
            $this->table(
                ['ID', 'Route', 'From → To', 'Time (min)', 'Distance (km)', 'Speed (km/h)'],
                \App\Models\TravelTime::latest('updated_at')
                    ->limit(5)
                    ->get(['id', 'route_name', 'from_location', 'to_location', 'travel_time_seconds', 'distance_meters', 'average_speed_kmh'])
                    ->map(fn ($t) => [
                        $t->id,
                        substr($t->route_name ?? 'N/A', 0, 20),
                        substr(($t->from_location ?? 'N/A').' → '.($t->to_location ?? 'N/A'), 0, 30),
                        $t->travel_time_seconds ? round($t->travel_time_seconds / 60, 1).' min' : 'N/A',
                        $t->distance_meters ? round($t->distance_meters / 1000, 1).' km' : 'N/A',
                        $t->average_speed_kmh ?? 'N/A',
                    ])
                    ->toArray()
            );
        }

        return self::SUCCESS;
    }
}
