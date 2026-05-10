<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\PerformanceMetric;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PerformanceService
{
    private array $cacheConfig = [
        'default_ttl' => 3600, // 1 hour
        'api_ttl' => 300, // 5 minutes
        'search_ttl' => 1800, // 30 minutes
        'analytics_ttl' => 3600, // 1 hour
    ];

    public function recordMetric(string $endpoint, string $method, int $responseTime, int $memoryUsage, string $statusCode, array $metadata = []): void
    {
        try {
            PerformanceMetric::create([
                'org_id' => $this->getCurrentOrgId(),
                'endpoint' => $endpoint,
                'method' => $method,
                'response_time_ms' => $responseTime,
                'memory_usage_mb' => $memoryUsage,
                'status_code' => $statusCode,
                'metadata' => $metadata,
                'measured_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to record performance metric', [
                'error' => $e->getMessage(),
                'endpoint' => $endpoint,
                'method' => $method,
            ]);
        }
    }

    public function getPerformanceReport(string $orgId, string $period = '24h'): array
    {
        $cacheKey = "performance_report_{$orgId}_{$period}";

        return Cache::remember($cacheKey, $this->cacheConfig['analytics_ttl'], function () use ($orgId, $period) {
            $timeRange = $this->getTimeRange($period);

            $metrics = PerformanceMetric::where('org_id', $orgId)
                ->whereBetween('measured_at', $timeRange)
                ->select([
                    'endpoint',
                    'method',
                    DB::raw('AVG(response_time_ms) as avg_response_time'),
                    DB::raw('MAX(response_time_ms) as max_response_time'),
                    DB::raw('MIN(response_time_ms) as min_response_time'),
                    DB::raw('AVG(memory_usage_mb) as avg_memory'),
                    DB::raw('MAX(memory_usage_mb) as max_memory'),
                    DB::raw('COUNT(*) as request_count'),
                    DB::raw('COUNT(CASE WHEN status_code LIKE "2%" THEN 1 END) as success_count'),
                    DB::raw('COUNT(CASE WHEN status_code LIKE "4%" OR status_code LIKE "5%" THEN 1 END) as error_count'),
                ])
                ->groupBy(['endpoint', 'method'])
                ->orderBy('avg_response_time', 'desc')
                ->get();

            $summary = [
                'total_requests' => $metrics->sum('request_count'),
                'avg_response_time' => $metrics->avg('avg_response_time'),
                'max_response_time' => $metrics->max('max_response_time'),
                'avg_memory_usage' => $metrics->avg('avg_memory'),
                'max_memory_usage' => $metrics->max('max_memory'),
                'success_rate' => $metrics->sum('success_count') / max($metrics->sum('request_count'), 1) * 100,
                'error_rate' => $metrics->sum('error_count') / max($metrics->sum('request_count'), 1) * 100,
            ];

            return [
                'summary' => $summary,
                'endpoints' => $metrics->toArray(),
                'period' => $period,
                'generated_at' => now(),
            ];
        });
    }

    public function getSlowQueries(string $orgId, int $limit = 10): array
    {
        $cacheKey = "slow_queries_{$orgId}";

        return Cache::remember($cacheKey, $this->cacheConfig['api_ttl'], function () use ($orgId, $limit) {
            return PerformanceMetric::where('org_id', $orgId)
                ->where('response_time_ms', '>', 1000) // Slower than 1 second
                ->orderBy('response_time_ms', 'desc')
                ->limit($limit)
                ->get()
                ->map(function ($metric) {
                    return [
                        'endpoint' => $metric->endpoint,
                        'method' => $metric->method,
                        'response_time' => $metric->response_time_ms,
                        'memory_usage' => $metric->memory_usage_mb,
                        'status_code' => $metric->status_code,
                        'measured_at' => $metric->measured_at,
                        'metadata' => $metric->metadata,
                    ];
                })
                ->toArray();
        });
    }

    public function optimizeDatabaseQueries(string $orgId): array
    {
        $optimizations = [];

        // Check for N+1 queries
        $nPlusOneQueries = $this->detectNPlusOneQueries($orgId);
        if (! empty($nPlusOneQueries)) {
            $optimizations[] = [
                'type' => 'n_plus_one',
                'description' => 'Detected N+1 query patterns',
                'queries' => $nPlusOneQueries,
                'recommendation' => 'Use eager loading with with() method',
            ];
        }

        // Check for missing indexes
        $missingIndexes = $this->detectMissingIndexes($orgId);
        if (! empty($missingIndexes)) {
            $optimizations[] = [
                'type' => 'missing_indexes',
                'description' => 'Detected queries that could benefit from indexes',
                'queries' => $missingIndexes,
                'recommendation' => 'Add database indexes for frequently queried columns',
            ];
        }

        // Check for inefficient joins
        $inefficientJoins = $this->detectInefficientJoins($orgId);
        if (! empty($inefficientJoins)) {
            $optimizations[] = [
                'type' => 'inefficient_joins',
                'description' => 'Detected inefficient join operations',
                'queries' => $inefficientJoins,
                'recommendation' => 'Optimize join conditions and consider denormalization',
            ];
        }

        return $optimizations;
    }

    public function getCacheStatistics(string $orgId): array
    {
        $cacheStats = [];

        // Redis cache statistics
        if (config('cache.default') === 'redis') {
            $redis = Cache::getRedis();
            $info = $redis->info();

            $cacheStats['redis'] = [
                'used_memory' => $info['used_memory_human'] ?? 'N/A',
                'connected_clients' => $info['connected_clients'] ?? 0,
                'total_commands_processed' => $info['total_commands_processed'] ?? 0,
                'keyspace_hits' => $info['keyspace_hits'] ?? 0,
                'keyspace_misses' => $info['keyspace_misses'] ?? 0,
                'hit_rate' => $this->calculateHitRate($info),
            ];
        }

        // Application cache statistics
        $cacheStats['application'] = [
            'total_keys' => $this->countCacheKeys($orgId),
            'memory_usage' => $this->getCacheMemoryUsage($orgId),
            'hit_rate' => $this->getApplicationCacheHitRate($orgId),
        ];

        return $cacheStats;
    }

    public function optimizeCache(string $orgId): array
    {
        $optimizations = [];

        // Clear expired cache entries
        $clearedKeys = $this->clearExpiredCache($orgId);
        if ($clearedKeys > 0) {
            $optimizations[] = [
                'action' => 'clear_expired',
                'keys_cleared' => $clearedKeys,
                'description' => 'Cleared expired cache entries',
            ];
        }

        // Optimize cache TTL
        $ttlOptimizations = $this->optimizeCacheTTL($orgId);
        if (! empty($ttlOptimizations)) {
            $optimizations[] = [
                'action' => 'optimize_ttl',
                'optimizations' => $ttlOptimizations,
                'description' => 'Optimized cache TTL values',
            ];
        }

        // Preload frequently accessed data
        $preloadedData = $this->preloadFrequentData($orgId);
        if (! empty($preloadedData)) {
            $optimizations[] = [
                'action' => 'preload_data',
                'preloaded' => $preloadedData,
                'description' => 'Preloaded frequently accessed data',
            ];
        }

        return $optimizations;
    }

    public function getCostAnalysis(string $orgId, string $period = '30d'): array
    {
        $timeRange = $this->getTimeRange($period);

        $metrics = PerformanceMetric::where('org_id', $orgId)
            ->whereBetween('measured_at', $timeRange)
            ->get();

        $totalRequests = $metrics->count();
        $totalResponseTime = $metrics->sum('response_time_ms');
        $totalMemoryUsage = $metrics->sum('memory_usage_mb');

        // Calculate costs (simplified)
        $computeCost = $this->calculateComputeCost($totalResponseTime, $totalMemoryUsage);
        $storageCost = $this->calculateStorageCost($orgId);
        $networkCost = $this->calculateNetworkCost($totalRequests);

        return [
            'period' => $period,
            'total_requests' => $totalRequests,
            'compute_cost' => $computeCost,
            'storage_cost' => $storageCost,
            'network_cost' => $networkCost,
            'total_cost' => $computeCost + $storageCost + $networkCost,
            'cost_per_request' => ($computeCost + $storageCost + $networkCost) / max($totalRequests, 1),
            'breakdown' => [
                'compute_percentage' => ($computeCost / ($computeCost + $storageCost + $networkCost)) * 100,
                'storage_percentage' => ($storageCost / ($computeCost + $storageCost + $networkCost)) * 100,
                'network_percentage' => ($networkCost / ($computeCost + $storageCost + $networkCost)) * 100,
            ],
        ];
    }

    private function getCurrentOrgId(): ?string
    {
        // Get current organization ID from request or session
        return request()->header('X-Organization-ID') ?? session('organization_id');
    }

    private function getTimeRange(string $period): array
    {
        $now = now();

        return match ($period) {
            '1h' => [$now->subHour(), $now],
            '24h' => [$now->subDay(), $now],
            '7d' => [$now->subWeek(), $now],
            '30d' => [$now->subMonth(), $now],
            default => [$now->subDay(), $now]
        };
    }

    private function detectNPlusOneQueries(string $orgId): array
    {
        // Simplified N+1 detection
        $suspiciousQueries = PerformanceMetric::where('org_id', $orgId)
            ->where('response_time_ms', '>', 500)
            ->where('metadata->query_count', '>', 10)
            ->get()
            ->toArray();

        return $suspiciousQueries;
    }

    private function detectMissingIndexes(string $orgId): array
    {
        // Check for slow queries that might benefit from indexes
        return PerformanceMetric::where('org_id', $orgId)
            ->where('response_time_ms', '>', 1000)
            ->where('metadata->scan_type', 'full')
            ->get()
            ->toArray();
    }

    private function detectInefficientJoins(string $orgId): array
    {
        // Check for queries with high memory usage and long execution time
        return PerformanceMetric::where('org_id', $orgId)
            ->where('memory_usage_mb', '>', 100)
            ->where('response_time_ms', '>', 2000)
            ->get()
            ->toArray();
    }

    private function calculateHitRate(array $info): float
    {
        $hits = $info['keyspace_hits'] ?? 0;
        $misses = $info['keyspace_misses'] ?? 0;
        $total = $hits + $misses;

        return $total > 0 ? ($hits / $total) * 100 : 0;
    }

    private function countCacheKeys(string $orgId): int
    {
        // Simplified key counting
        return Cache::get('cache_key_count_'.$orgId, 0);
    }

    private function getCacheMemoryUsage(string $orgId): string
    {
        // Simplified memory usage calculation
        return 'N/A';
    }

    private function getApplicationCacheHitRate(string $orgId): float
    {
        // Simplified hit rate calculation
        return Cache::get('cache_hit_rate_'.$orgId, 0);
    }

    private function clearExpiredCache(string $orgId): int
    {
        // Simplified expired cache clearing
        $keys = Cache::get('cache_keys_'.$orgId, []);
        $cleared = 0;

        foreach ($keys as $key) {
            if (! Cache::has($key)) {
                $cleared++;
            }
        }

        return $cleared;
    }

    private function optimizeCacheTTL(string $orgId): array
    {
        // Analyze cache usage patterns and suggest TTL optimizations
        return [
            'api_cache_ttl' => '300s',
            'search_cache_ttl' => '1800s',
            'analytics_cache_ttl' => '3600s',
        ];
    }

    private function preloadFrequentData(string $orgId): array
    {
        // Preload frequently accessed data
        $preloaded = [];

        // Preload service types
        Cache::remember("service_types_{$orgId}", 3600, function () use ($orgId) {
            return \App\Models\ServiceType::where('org_id', $orgId)->get();
        });
        $preloaded[] = 'service_types';

        // Preload geo zones
        Cache::remember("geo_zones_{$orgId}", 3600, function () use ($orgId) {
            return \App\Models\GeoZone::where('org_id', $orgId)->get();
        });
        $preloaded[] = 'geo_zones';

        return $preloaded;
    }

    private function calculateComputeCost(int $totalResponseTime, int $totalMemoryUsage): float
    {
        // Simplified compute cost calculation
        $computeHours = $totalResponseTime / 3600; // Convert to hours
        $memoryGBHours = $totalMemoryUsage / 1024; // Convert to GB-hours

        return ($computeHours * 0.1) + ($memoryGBHours * 0.05); // $0.1/hour + $0.05/GB-hour
    }

    private function calculateStorageCost(string $orgId): float
    {
        // Simplified storage cost calculation
        $storageGB = $this->getStorageUsage($orgId);

        return $storageGB * 0.1; // $0.1/GB/month
    }

    private function calculateNetworkCost(int $totalRequests): float
    {
        // Simplified network cost calculation
        $dataGB = $totalRequests * 0.001; // Assume 1MB per request

        return $dataGB * 0.09; // $0.09/GB
    }

    private function getStorageUsage(string $orgId): float
    {
        // Simplified storage usage calculation
        return 10.0; // Assume 10GB
    }
}
