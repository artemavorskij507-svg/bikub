<?php

namespace App\Services\Pricing;

use Illuminate\Contracts\Cache\Repository as CacheRepository;

class DemandService
{
    public function __construct(
        private readonly CacheRepository $cache,
    ) {}

    public function getMultiplier(?string $zone): float
    {
        if (! $zone) {
            return 1.0;
        }

        $key = $this->zoneKey($zone);
        $metrics = $this->cache->get($key, ['requests_per_minute' => 0]);
        $rpm = (int) ($metrics['requests_per_minute'] ?? 0);

        if ($rpm >= 60) {
            return 1.35;
        }

        if ($rpm >= 40) {
            return 1.2;
        }

        if ($rpm >= 20) {
            return 1.1;
        }

        return 1.0;
    }

    public function recordRequest(?string $zone): void
    {
        if (! $zone) {
            return;
        }

        $key = $this->zoneKey($zone).':counter';
        $this->cache->add($key, 0, now()->addMinutes(2));
        $this->cache->increment($key);
    }

    public function storeMetrics(string $zone, array $metrics, int $ttlSeconds = 120): void
    {
        $payload = array_merge([
            'requests_per_minute' => 0,
            'active_orders' => 0,
            'updated_at' => now()->toIso8601String(),
        ], $metrics);

        $this->cache->put($this->zoneKey($zone), $payload, now()->addSeconds($ttlSeconds));
    }

    private function zoneKey(string $zone): string
    {
        return 'demand:zone:'.strtolower($zone);
    }
}
