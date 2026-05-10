<?php

namespace App\Domain\Routing\Actions;

use Illuminate\Support\Facades\Http;

class CheckRoutingProviderHealthAction
{
    public function execute(): array
    {
        $provider = (string) config('routing.default_provider', 'null');
        $checkedAt = now()->toIso8601String();

        if ($provider !== 'osrm' || ! (bool) config('routing.osrm.enabled', false)) {
            return [
                'provider' => $provider,
                'reachable' => false,
                'latency_ms' => null,
                'error' => 'provider_disabled_or_not_osrm',
                'checked_at' => $checkedAt,
            ];
        }

        $baseUrl = rtrim((string) config('routing.osrm.base_url', ''), '/');
        if ($baseUrl === '') {
            return [
                'provider' => 'osrm',
                'reachable' => false,
                'latency_ms' => null,
                'error' => 'osrm_base_url_missing',
                'checked_at' => $checkedAt,
            ];
        }

        $timeout = max(1, (int) config('routing.osrm.timeout_seconds', 3));
        $url = $baseUrl.'/route/v1/driving/10.7522,59.9139;10.7695,59.9311';

        try {
            $startedAt = microtime(true);
            $response = Http::timeout($timeout)->get($url, [
                'overview' => 'false',
                'alternatives' => 'false',
                'steps' => 'false',
                'annotations' => 'false',
            ]);
            $latencyMs = (int) round((microtime(true) - $startedAt) * 1000);

            if (! $response->ok()) {
                return [
                    'provider' => 'osrm',
                    'reachable' => false,
                    'latency_ms' => $latencyMs,
                    'error' => 'osrm_http_'.$response->status(),
                    'checked_at' => $checkedAt,
                ];
            }

            $route = data_get($response->json(), 'routes.0');
            if (! is_array($route)) {
                return [
                    'provider' => 'osrm',
                    'reachable' => false,
                    'latency_ms' => $latencyMs,
                    'error' => 'osrm_no_route',
                    'checked_at' => $checkedAt,
                ];
            }

            return [
                'provider' => 'osrm',
                'reachable' => true,
                'latency_ms' => $latencyMs,
                'error' => null,
                'checked_at' => $checkedAt,
            ];
        } catch (\Throwable $exception) {
            return [
                'provider' => 'osrm',
                'reachable' => false,
                'latency_ms' => null,
                'error' => $exception->getMessage(),
                'checked_at' => $checkedAt,
            ];
        }
    }
}

