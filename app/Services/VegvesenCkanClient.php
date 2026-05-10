<?php

namespace App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

class VegvesenCkanClient
{
    private string $baseUrl;

    private array $resourceFormats;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('vegvesen.ckan_base_url'), '/');
        $this->resourceFormats = array_map('strtoupper', config('vegvesen.resource_formats', []));
    }

    public function search(string $query, int $limit = 50): array
    {
        $resp = Http::timeout(15)->get($this->baseUrl.'/package_search', [
            'q' => $query,
            'rows' => $limit,
        ])->throw();
        $data = $resp->json('result.results', []);

        $filtered = [];
        foreach ($data as $pkg) {
            $resources = Arr::get($pkg, 'resources', []);
            $resources = array_values(array_filter($resources, function ($r) {
                $fmt = strtoupper($r['format'] ?? '');

                return in_array($fmt, $this->resourceFormats, true);
            }));
            $pkg['resources'] = $resources;
            $filtered[] = $pkg;
        }

        return $filtered;
    }

    public function getPackage(string $packageId): ?array
    {
        try {
            $resp = Http::timeout(15)->get($this->baseUrl.'/package_show', [
                'id' => $packageId,
            ])->throw();

            return $resp->json('result', null);
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function extractResourceUrl(array $resource, string $packageId): ?string
    {
        // Try direct URL fields first
        $url = $resource['url'] ?? $resource['download_url'] ?? $resource['access_url'] ?? null;

        if ($url && $url !== '') {
            return $url;
        }

        // For WFS/WMS resources, get full package info to find service URLs
        $package = $this->getPackage($packageId);
        if ($package) {
            // Check resources in package for matching format
            $format = strtoupper($resource['format'] ?? '');
            $resourceName = $resource['name'] ?? '';

            foreach ($package['resources'] ?? [] as $pkgResource) {
                $pkgFormat = strtoupper($pkgResource['format'] ?? '');
                // Match by format and name if available
                if ($pkgFormat === $format) {
                    $pkgUrl = $pkgResource['url'] ?? $pkgResource['download_url'] ?? $pkgResource['access_url'] ?? null;
                    if ($pkgUrl && $pkgUrl !== '') {
                        return $pkgUrl;
                    }
                }
            }

            // Check extras for service URLs
            foreach ($package['extras'] ?? [] as $extra) {
                $key = strtolower($extra['key'] ?? '');
                if (str_contains($key, 'wfs') || str_contains($key, 'wms') || str_contains($key, 'service')) {
                    return $extra['value'] ?? null;
                }
            }
        }

        return null;
    }

    public function getFullPackageResources(string $packageId): array
    {
        $package = $this->getPackage($packageId);
        if (! $package) {
            return [];
        }

        // Return all resources with their full metadata
        $resources = [];
        foreach ($package['resources'] ?? [] as $resource) {
            $fmt = strtoupper($resource['format'] ?? '');
            if (in_array($fmt, $this->resourceFormats, true)) {
                $resources[] = $resource;
            }
        }

        return $resources;
    }
}
