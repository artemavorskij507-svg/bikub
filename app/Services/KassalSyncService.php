<?php

namespace App\Services;

use App\Models\Product;
use App\Models\RetailStore;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class KassalSyncService
{
    protected string $base;

    protected string $key;

    public function __construct()
    {
        $this->base = config('kassal.base_url');
        $this->key = config('kassal.api_key');
    }

    protected function get(string $endpoint): array
    {
        if (empty($this->key)) {
            throw new Exception('KASSAL_API_KEY is not set in .env file');
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->key}",
            ])->timeout(30)->get("{$this->base}/{$endpoint}");

            if ($response->failed()) {
                Log::error('Kassal API request failed', [
                    'endpoint' => $endpoint,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw new Exception("Kassal API request failed: {$response->status()}");
            }

            return $response->json() ?? [];
        } catch (Exception $e) {
            Log::error('Kassal API error', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function syncStores(): int
    {
        $stores = $this->get('physical-stores');
        $count = 0;

        foreach ($stores['data'] ?? [] as $s) {
            try {
                RetailStore::updateOrCreate(
                    ['kassal_id' => $s['id']],
                    [
                        'name' => $s['name'] ?? 'Unknown',
                        'slug' => Str::slug($s['name'] ?? 'unknown'),
                        'chain_name' => $s['chain'] ?? null,
                        'brand' => $s['chain'] ?? null,
                        'address' => $s['address'] ?? null,
                        'city' => $s['city'] ?? 'Narvik',
                        'latitude' => isset($s['latitude']) ? (float) $s['latitude'] : null,
                        'longitude' => isset($s['longitude']) ? (float) $s['longitude'] : null,
                        'is_active' => true,
                    ]
                );
                $count++;
            } catch (Exception $e) {
                Log::warning('Failed to sync store', [
                    'kassal_id' => $s['id'] ?? null,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $count;
    }

    public function syncProducts(): int
    {
        $items = $this->get('products');
        $count = 0;

        foreach ($items['data'] ?? [] as $p) {
            try {
                Product::updateOrCreate(
                    ['kassal_id' => $p['id']],
                    [
                        'name' => $p['name'] ?? 'Unknown',
                        'slug' => Str::slug($p['name'] ?? 'unknown'),
                        'canonical_name' => $p['name'] ?? 'Unknown',
                        'image_url' => $p['image'] ?? null,
                        'is_active' => true,
                    ]
                );
                $count++;
            } catch (Exception $e) {
                Log::warning('Failed to sync product', [
                    'kassal_id' => $p['id'] ?? null,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $count;
    }

    public function syncAll(): array
    {
        $storesCount = $this->syncStores();
        $productsCount = $this->syncProducts();

        return [
            'stores' => $storesCount,
            'products' => $productsCount,
        ];
    }
}
