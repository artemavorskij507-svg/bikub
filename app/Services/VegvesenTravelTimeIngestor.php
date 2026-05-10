<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VegvesenTravelTimeIngestor
{
    public function ingestNarvik(array $resources): int
    {
        // Narvik approx bbox
        $centerLat = 68.438;
        $centerLng = 17.427;
        $d = 1.0;
        $minLat = $centerLat - 0.5 * $d;
        $maxLat = $centerLat + 0.5 * $d;
        $minLng = $centerLng - 0.5 * $d;
        $maxLng = $centerLng + 0.5 * $d;

        $stored = 0;
        foreach ($resources as $r) {
            $url = $r['url'] ?? $r['download_url'] ?? $r['access_url'] ?? null;
            $format = strtoupper($r['format'] ?? 'UNKNOWN');

            // For WFS resources, construct GetFeature request
            if ($format === 'WFS') {
                if (! $url || $url === '') {
                    $url = 'https://www.vegvesen.no/trafikk/reisetider/reisetider.wfs';
                }

                $bbox = "{$minLng},{$minLat},{$maxLng},{$maxLat},EPSG:4326";
                $layerName = $r['name'] ?? 'reisetider';

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
                    Log::info("Constructed WFS GetFeature URL for Reisetider: {$url}");
                }
            } elseif ($format === 'WMS') {
                Log::debug('Skipping WMS resource');

                continue;
            } elseif (! $url || $url === '') {
                Log::debug("Skipping resource without URL: format={$format}");

                continue;
            }

            Log::info("Fetching travel time resource: {$format} - {$url}");

            try {
                $resp = Http::timeout(30)->get($url);
            } catch (\Throwable $e) {
                Log::warning("Travel time fetch failed: {$url} :: {$e->getMessage()}");

                continue;
            }

            if (! $resp->ok()) {
                Log::warning("HTTP {$resp->status()} for {$url}");

                continue;
            }

            $json = null;
            $body = $resp->body();

            try {
                $json = $resp->json();
            } catch (\Throwable $e) {
                Log::debug('Response is not JSON, trying XML parsing');
                $contentType = strtolower($resp->header('Content-Type') ?? '');
                if (str_contains($contentType, 'xml')) {
                    $xml = @simplexml_load_string($body);
                    if ($xml) {
                        $json = $this->parseTravelTimeXml($xml, $minLat, $minLng, $maxLat, $maxLng);
                    }
                }
            }

            if (! $json) {
                Log::debug("Could not parse travel time data from: {$url}");

                continue;
            }

            $items = $json['features'] ?? ($json['items'] ?? []);
            foreach ($items as $item) {
                $id = $item['id'] ?? ($item['properties']['id'] ?? null);
                $props = $item['properties'] ?? [];
                $geom = $item['geometry'] ?? null;

                // Extract route information
                $routeName = $props['route'] ?? $props['name'] ?? $props['route_name'] ?? null;
                $fromLoc = $props['from'] ?? $props['from_location'] ?? null;
                $toLoc = $props['to'] ?? $props['to_location'] ?? null;

                // Extract coordinates
                $fromLat = $props['from_lat'] ?? null;
                $fromLng = $props['from_lng'] ?? null;
                $toLat = $props['to_lat'] ?? null;
                $toLng = $props['to_lng'] ?? null;

                // Try to extract from geometry (LineString)
                if ($geom && ($geom['type'] ?? '') === 'LineString') {
                    $coords = $geom['coordinates'] ?? [];
                    if (count($coords) >= 2) {
                        $fromLng = $coords[0][0] ?? null;
                        $fromLat = $coords[0][1] ?? null;
                        $lastIdx = count($coords) - 1;
                        $toLng = $coords[$lastIdx][0] ?? null;
                        $toLat = $coords[$lastIdx][1] ?? null;
                    }
                }

                // Filter by Narvik region
                if ($fromLat && $fromLng) {
                    if ($fromLat < $minLat || $fromLat > $maxLat || $fromLng < $minLng || $fromLng > $maxLng) {
                        continue;
                    }
                }

                $travelTimeSeconds = $props['travel_time'] ?? $props['time_seconds'] ?? $props['duration'] ?? null;
                $distanceMeters = $props['distance'] ?? $props['distance_meters'] ?? null;

                if (! $id) {
                    $id = md5(json_encode($item));
                }

                // Calculate speed if time and distance available
                $avgSpeed = null;
                if ($travelTimeSeconds && $distanceMeters && $travelTimeSeconds > 0) {
                    $hours = $travelTimeSeconds / 3600;
                    $km = $distanceMeters / 1000;
                    $avgSpeed = $hours > 0 ? round($km / $hours, 2) : null;
                }

                DB::table('travel_times')->updateOrInsert(
                    ['external_id' => (string) $id],
                    [
                        'route_name' => $routeName,
                        'from_location' => $fromLoc,
                        'to_location' => $toLoc,
                        'from_lat' => $fromLat,
                        'from_lng' => $fromLng,
                        'to_lat' => $toLat,
                        'to_lng' => $toLng,
                        'travel_time_seconds' => $travelTimeSeconds,
                        'distance_meters' => $distanceMeters,
                        'average_speed_kmh' => $avgSpeed,
                        'status' => $props['status'] ?? 'normal',
                        'measured_at' => $props['measured_at'] ?? $props['timestamp'] ?? now(),
                        'geometry' => $geom ? json_encode($geom) : null,
                        'meta' => json_encode($props),
                        'source_url' => $url,
                        'updated_at' => now(),
                        'created_at' => DB::raw('COALESCE(created_at, NOW())'),
                    ]
                );
                $stored++;
            }
        }

        return $stored;
    }

    protected function parseTravelTimeXml(\SimpleXMLElement $xml, float $minLat, float $minLng, float $maxLat, float $maxLng): ?array
    {
        // Parse DateX II XML format for travel times
        $features = [];

        // Try DateX II structure or simple XML
        $items = $xml->xpath('//travelTimeMeasurement | //route | //measurement');

        foreach ($items as $item) {
            $routeName = (string) ($item->routeName ?? $item->name ?? '');
            $from = (string) ($item->from ?? $item->startPoint ?? '');
            $to = (string) ($item->to ?? $item->endPoint ?? '');
            $timeSeconds = (int) ($item->travelTime ?? $item->duration ?? 0);
            $distanceMeters = (int) ($item->distance ?? 0);

            // Try to extract coordinates
            $fromLat = (float) ($item->fromLat ?? $item->startLat ?? 0);
            $fromLng = (float) ($item->fromLng ?? $item->startLng ?? 0);
            $toLat = (float) ($item->toLat ?? $item->endLat ?? 0);
            $toLng = (float) ($item->toLng ?? $item->endLng ?? 0);

            if ($fromLat && $fromLng) {
                if ($fromLat < $minLat || $fromLat > $maxLat || $fromLng < $minLng || $fromLng > $maxLng) {
                    continue;
                }
            }

            $id = md5($routeName.$from.$to);
            $features[] = [
                'id' => $id,
                'properties' => [
                    'route_name' => $routeName,
                    'from_location' => $from,
                    'to_location' => $to,
                    'from_lat' => $fromLat ?: null,
                    'from_lng' => $fromLng ?: null,
                    'to_lat' => $toLat ?: null,
                    'to_lng' => $toLng ?: null,
                    'travel_time' => $timeSeconds ?: null,
                    'distance' => $distanceMeters ?: null,
                ],
                'geometry' => null,
            ];
        }

        return ['features' => $features];
    }
}
