<?php

namespace App\Console\Commands;

use App\Services\VegvesenCkanClient;
use App\Services\VegvesenIncidentIngestor;
use Illuminate\Console\Command;

class VegvesenIngestIncidentsCommand extends Command
{
    protected $signature = 'vegvesen:ingest-incidents {--query=} {--limit=50}';

    protected $description = 'Ingest Trafikkmeldinger incidents for a region (default Narvik)';

    public function handle(VegvesenCkanClient $ckan, VegvesenIncidentIngestor $ingestor): int
    {
        $query = $this->option('query') ?: (config('vegvesen.default_query').' Trafikkmeldinger');
        $limit = (int) $this->option('limit');

        $this->info("Searching CKAN for incidents: {$query}");
        $packages = $ckan->search($query, $limit);

        $this->info('Found packages: '.count($packages));

        $resources = [];
        foreach ($packages as $pkg) {
            $pkgName = $pkg['name'] ?? 'unknown';
            $pkgTitle = $pkg['title'] ?? 'No title';

            // For Trafikkmeldinger, get full package resources with complete metadata
            if ($pkgName === 'trafikkmeldinger') {
                $this->info("Fetching full metadata for package: {$pkgName}");
                $fullResources = $ckan->getFullPackageResources($pkgName);
                $fullResourceCount = count($fullResources);
                $this->line("  Package: {$pkgName} - {$pkgTitle} ({$fullResourceCount} resources from full metadata)");

                foreach ($fullResources as $r) {
                    $fmt = strtoupper($r['format'] ?? 'UNKNOWN');
                    $url = $r['url'] ?? $r['download_url'] ?? $r['access_url'] ?? null;

                    if (! $url || $url === '') {
                        // Try to construct WFS URL from known Vegvesen patterns
                        if ($fmt === 'WFS') {
                            // Common Vegvesen WFS endpoint pattern
                            $url = 'https://www.vegvesen.no/trafikk/trafikkMeldinger/trafikkMeldinger.wfs';
                        } elseif ($fmt === 'WMS') {
                            // Skip WMS for data ingestion
                            continue;
                        }
                    }

                    $r['url'] = $url;
                    $this->line("    Resource: {$fmt} - {$url}");
                    $resources[] = $r;
                }
            } else {
                // For other packages, use resources from search
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
            $this->warn('No resources found. Try adjusting query or check resource formats in config.');

            return self::FAILURE;
        }

        $stored = $ingestor->ingestNarvik($resources);
        $this->info("✓ Stored/updated incidents: {$stored}");

        if ($stored > 0) {
            $this->line("\nSample incidents:");
            $this->table(
                ['ID', 'Title', 'Severity', 'Status', 'Location'],
                \App\Models\TrafficIncident::latest('updated_at')
                    ->limit(5)
                    ->get(['id', 'title', 'severity', 'status', 'lat', 'lng'])
                    ->map(fn ($i) => [
                        $i->id,
                        substr($i->title ?? 'N/A', 0, 30),
                        $i->severity ?? 'N/A',
                        $i->status ?? 'N/A',
                        ($i->lat && $i->lng) ? "{$i->lat}, {$i->lng}" : 'N/A',
                    ])
                    ->toArray()
            );
        }

        return self::SUCCESS;
    }
}
