<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VegvesenIncidentIngestor
{
    public function ingestNarvik(array $resources): int
    {
        // Narvik approx bbox
        $centerLat = 68.438;
        $centerLng = 17.427;
        $d = 1.0; // ~1 degree box
        $minLat = $centerLat - 0.5 * $d;
        $maxLat = $centerLat + 0.5 * $d;
        $minLng = $centerLng - 0.5 * $d;
        $maxLng = $centerLng + 0.5 * $d;

        $stored = 0;
        foreach ($resources as $r) {
            // Try multiple URL fields
            $url = $r['url'] ?? $r['download_url'] ?? $r['access_url'] ?? null;
            $format = strtoupper($r['format'] ?? 'UNKNOWN');

            // For WFS resources, construct GetFeature request URL
            if ($format === 'WFS') {
                if (! $url || $url === '') {
                    // Try known Vegvesen WFS base URLs
                    $url = 'https://www.vegvesen.no/trafikk/trafikkMeldinger/trafikkMeldinger.wfs';
                }

                // Convert WFS base URL to GetFeature request
                $bbox = "{$minLng},{$minLat},{$maxLng},{$maxLat},EPSG:4326";
                $layerName = $r['name'] ?? 'trafikkmeldinger';

                // Parse base URL and construct GetFeature request
                $parsed = parse_url($url);
                if ($parsed) {
                    $basePath = rtrim($parsed['path'] ?? '/wfs', '/');
                    if (! str_ends_with($basePath, '/wfs')) {
                        $basePath = dirname($basePath).'/wfs';
                    }

                    $wfsParams = http_build_query([
                        'service' => 'WFS',
                        'version' => '1.1.0',
                        'request' => 'GetFeature',
                        'typeName' => $layerName,
                        'outputFormat' => 'application/json',
                        'bbox' => $bbox,
                        'maxFeatures' => 1000,
                    ]);

                    $url = ($parsed['scheme'] ?? 'https').'://'.($parsed['host'] ?? 'www.vegvesen.no').$basePath.'?'.$wfsParams;
                    Log::info("Constructed WFS GetFeature URL: {$url}");
                }
            } elseif ($format === 'WMS') {
                Log::debug('Skipping WMS resource (no direct data endpoint)');

                continue;
            } elseif (! $url || $url === '') {
                Log::debug("Skipping resource without URL: format={$format}, name=".($r['name'] ?? 'N/A'));

                continue;
            }

            Log::info("Fetching resource: {$format} - {$url}");

            try {
                $resp = Http::timeout(20)->get($url);
            } catch (\Throwable $e) {
                Log::warning("Incident fetch failed: {$url} :: {$e->getMessage()}");

                continue;
            }

            if (! $resp->ok()) {
                Log::warning("HTTP {$resp->status()} for {$url}");

                continue;
            }

            Log::debug("Response OK: {$url}, Content-Type: ".($resp->header('Content-Type') ?? 'unknown'));

            $json = null;
            $body = $resp->body();

            // Try JSON first
            try {
                $json = $resp->json();
            } catch (\Throwable $e) {
                // Try to parse as XML/RSS if content type indicates XML
                $contentType = strtolower($resp->header('Content-Type') ?? '');
                if (str_contains($contentType, 'xml') || str_contains($contentType, 'rss')) {
                    $xml = @simplexml_load_string($body);
                    if ($xml) {
                        $json = $this->parseRssXml($xml, $minLat, $minLng, $maxLat, $maxLng);
                    }
                } elseif (str_contains($contentType, 'html')) {
                    // Try parsing HTML page for traffic messages
                    $json = $this->parseTrafficHtml($body, $minLat, $minLng, $maxLat, $maxLng);
                }
            }

            // If not JSON, try WFS GetFeature request
            if (! $json) {
                $format = strtoupper($r['format'] ?? '');
                if (in_array($format, ['WFS', 'WMS'], true)) {
                    // Try to construct WFS GetFeature request
                    $wfsUrl = $this->buildWfsUrl($url, $r, $minLng, $minLat, $maxLng, $maxLat);
                    if ($wfsUrl) {
                        try {
                            $wfsResp = Http::timeout(30)->get($wfsUrl);
                            if ($wfsResp->ok()) {
                                $json = $wfsResp->json();
                            }
                        } catch (\Throwable $e) {
                            Log::debug("WFS request failed: {$wfsUrl} :: {$e->getMessage()}");
                        }
                    }
                }

                if (! $json) {
                    Log::debug("Skipping non-JSON resource: {$url} (format: {$format})");

                    continue;
                }
            }

            $items = $json['features'] ?? ($json['items'] ?? []);
            foreach ($items as $item) {
                // Try GeoJSON-like
                $id = $item['id'] ?? ($item['properties']['id'] ?? null);
                $props = $item['properties'] ?? [];
                $geom = $item['geometry'] ?? null;
                // Extract coordinates from geometry
                $lat = $props['lat'] ?? null;
                $lng = $props['lng'] ?? null;
                if ((! $lat || ! $lng) && $geom) {
                    if (($geom['type'] ?? '') === 'Point') {
                        $coords = $geom['coordinates'] ?? null;
                        if (is_array($coords) && count($coords) >= 2) {
                            $lng = $coords[0];
                            $lat = $coords[1];
                        }
                    } elseif (($geom['type'] ?? '') === 'LineString' && isset($geom['coordinates'][0])) {
                        // Use first point of LineString
                        $coords = $geom['coordinates'][0];
                        if (is_array($coords) && count($coords) >= 2) {
                            $lng = $coords[0];
                            $lat = $coords[1];
                        }
                    }
                }
                if ($lat && $lng) {
                    if ($lat < $minLat || $lat > $maxLat || $lng < $minLng || $lng > $maxLng) {
                        continue;
                    }
                }

                if (! $id) {
                    $id = md5(json_encode($item));
                }

                DB::table('traffic_incidents')->updateOrInsert(
                    ['external_id' => (string) $id],
                    [
                        'title' => $props['title'] ?? ($props['event'] ?? null),
                        'description' => $props['description'] ?? null,
                        'severity' => $props['severity'] ?? null,
                        'status' => $props['status'] ?? null,
                        'starts_at' => $props['startTime'] ?? null,
                        'ends_at' => $props['endTime'] ?? null,
                        'lat' => $lat,
                        'lng' => $lng,
                        'geometry' => $geom ? json_encode($geom) : null,
                        'meta' => json_encode($props),
                        'source_url' => $url,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
                $stored++;
            }
        }

        return $stored;
    }

    protected function buildWfsUrl(string $baseUrl, array $resource, float $minLng, float $minLat, float $maxLng, float $maxLat): ?string
    {
        // Try to detect WFS endpoint from URL
        if (str_contains($baseUrl, 'geoserver') || str_contains($baseUrl, '/wfs')) {
            $parts = parse_url($baseUrl);
            $scheme = $parts['scheme'] ?? 'https';
            $host = $parts['host'] ?? '';
            $path = $parts['path'] ?? '';

            // Extract workspace and layer name if possible
            $workspace = '';
            $layer = $resource['name'] ?? 'traffic_incidents';

            // Try to extract from path like /geoserver/workspace/wfs or /workspace/wms
            if (preg_match('#/([^/]+)/wfs#i', $path, $m)) {
                $workspace = $m[1];
            }

            // Build WFS GetFeature URL
            $wfsPath = str_replace(['/wms', '/rest'], '', $path);
            $wfsPath = rtrim($wfsPath, '/').'/wfs';

            $params = http_build_query([
                'service' => 'WFS',
                'version' => '1.1.0',
                'request' => 'GetFeature',
                'typeName' => $workspace ? "{$workspace}:{$layer}" : $layer,
                'outputFormat' => 'application/json',
                'bbox' => "{$minLng},{$minLat},{$maxLng},{$maxLat},EPSG:4326",
                'maxFeatures' => 1000,
            ]);

            return "{$scheme}://{$host}{$wfsPath}?{$params}";
        }

        return null;
    }

    protected function parseRssXml(\SimpleXMLElement $xml, float $minLat, float $minLng, float $maxLat, float $maxLng): ?array
    {
        // Parse RSS/XML feed from Vegvesen Trafikkmeldinger
        $features = [];

        // Try RSS format
        $items = $xml->xpath('//item') ?: $xml->xpath('//channel/item');

        foreach ($items as $item) {
            $title = (string) ($item->title ?? '');
            $description = (string) ($item->description ?? '');
            $pubDate = (string) ($item->pubDate ?? '');

            // Try to extract location from description or geo tags
            $lat = null;
            $lng = null;

            // Try geo:lat and geo:long (RSS Geo extension)
            $geoLat = $item->children('geo', true)->lat ?? $item->xpath('.//geo:lat');
            $geoLong = $item->children('geo', true)->long ?? $item->xpath('.//geo:long');

            if ($geoLat && is_array($geoLat) && count($geoLat) > 0) {
                $lat = (float) (string) $geoLat[0];
            }
            if ($geoLong && is_array($geoLong) && count($geoLong) > 0) {
                $lng = (float) (string) $geoLong[0];
            }

            // If no geo tags, try to extract from description (common pattern in Norwegian traffic feeds)
            if (! $lat || ! $lng) {
                // Look for coordinates in description (e.g., "68.438, 17.427" or "lat: 68.438, lng: 17.427")
                if (preg_match('/(?:lat|bredde)[:\s]+([0-9.]+).*?(?:lng|long|lengde)[:\s]+([0-9.]+)/i', $description, $m)) {
                    $lat = (float) $m[1];
                    $lng = (float) $m[2];
                } elseif (preg_match('/([0-9]{1,2}\.[0-9]+)[,\s]+([0-9]{1,2}\.[0-9]+)/', $description, $m)) {
                    // Assume lat,lng format
                    $lat = (float) $m[1];
                    $lng = (float) $m[2];
                }
            }

            // Filter by Narvik region if coordinates available
            if ($lat && $lng) {
                if ($lat < $minLat || $lat > $maxLat || $lng < $minLng || $lng > $maxLng) {
                    continue;
                }
            }

            // Create GeoJSON-like feature
            $id = md5($title.$pubDate);
            $props = [
                'title' => $title,
                'description' => $description,
                'pubDate' => $pubDate,
            ];

            $feature = [
                'id' => $id,
                'properties' => $props,
                'geometry' => null,
            ];

            if ($lat && $lng) {
                $feature['geometry'] = [
                    'type' => 'Point',
                    'coordinates' => [$lng, $lat],
                ];
            }

            $features[] = $feature;
        }

        return ['features' => $features];
    }

    protected function parseTrafficHtml(string $html, float $minLat, float $minLng, float $maxLat, float $maxLng): ?array
    {
        // Try to extract traffic messages from HTML page
        // This is a fallback when RSS/XML/JSON are not available
        $features = [];

        // Use DOMDocument for HTML parsing
        libxml_use_internal_errors(true);
        $dom = new \DOMDocument;
        @$dom->loadHTML($html);

        // Try to find traffic message elements (common patterns in Norwegian traffic sites)
        $xpath = new \DOMXPath($dom);

        // Look for common patterns: articles, divs with traffic data, etc.
        $items = $xpath->query('//article | //div[contains(@class, "traffic")] | //div[contains(@class, "message")] | //li[contains(@class, "incident")]');

        if ($items && $items->length > 0) {
            foreach ($items as $item) {
                $title = '';
                $description = '';

                // Extract title
                $titleNodes = $xpath->query('.//h1 | .//h2 | .//h3 | .//span[contains(@class, "title")]', $item);
                if ($titleNodes && $titleNodes->length > 0) {
                    $title = trim($titleNodes->item(0)->textContent);
                }

                // Extract description/text
                $descNodes = $xpath->query('.//p | .//div[contains(@class, "description")] | .//span[contains(@class, "description")]', $item);
                if ($descNodes && $descNodes->length > 0) {
                    $description = trim($descNodes->item(0)->textContent);
                } else {
                    $description = trim($item->textContent);
                }

                if (empty($title) && empty($description)) {
                    continue;
                }

                // Try to extract coordinates from text
                $lat = null;
                $lng = null;
                $fullText = $title.' '.$description;

                // Look for coordinates in text (Norwegian/Narvik region patterns)
                if (preg_match('/(?:lat|bredde|latitude)[:\s]+([0-9]{1,2}\.[0-9]+).*?(?:lng|long|lengde|longitude)[:\s]+([0-9]{1,2}\.[0-9]+)/i', $fullText, $m)) {
                    $lat = (float) $m[1];
                    $lng = (float) $m[2];
                } elseif (preg_match('/\b(68\.[0-9]+)[,\s]+(17\.[0-9]+)\b/', $fullText, $m)) {
                    // Narvik area coordinates pattern (68.x, 17.x)
                    $lat = (float) $m[1];
                    $lng = (float) $m[2];
                }

                // Filter by region if coordinates available
                if ($lat && $lng) {
                    if ($lat < $minLat || $lat > $maxLat || $lng < $minLng || $lng > $maxLng) {
                        continue;
                    }
                }

                // Create feature
                $id = md5($title.$description.time());
                $feature = [
                    'id' => $id,
                    'properties' => [
                        'title' => $title ?: 'Traffic Message',
                        'description' => $description,
                    ],
                    'geometry' => null,
                ];

                if ($lat && $lng) {
                    $feature['geometry'] = [
                        'type' => 'Point',
                        'coordinates' => [$lng, $lat],
                    ];
                }

                $features[] = $feature;
            }
        }

        if (empty($features)) {
            return null;
        }

        return ['features' => $features];
    }
}
