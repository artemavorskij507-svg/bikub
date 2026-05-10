<?php

namespace App\Domain\Routing\Providers;

use App\Domain\Routing\Contracts\RouteMatrixProvider;
use App\Domain\Routing\DTO\RouteEtaResult;
use App\Domain\Routing\DTO\RouteLocation;
use Illuminate\Support\Facades\Http;

class OsrmRouteMatrixProvider implements RouteMatrixProvider
{
    public function estimateEta(RouteLocation $from, RouteLocation $to, array $context = []): RouteEtaResult
    {
        $baseUrl = rtrim((string) config('routing.osrm.base_url', ''), '/');
        if ($baseUrl === '') {
            return new RouteEtaResult(0, 0, 'osrm', false, 'osrm_base_url_missing');
        }

        $timeout = max(1, (int) config('routing.osrm.timeout_seconds', 3));
        $coordinates = $from->lng.','.$from->lat.';'.$to->lng.','.$to->lat;
        $url = $baseUrl.'/route/v1/driving/'.$coordinates;

        try {
            $response = Http::timeout($timeout)->get($url, [
                'overview' => 'false',
                'alternatives' => 'false',
                'steps' => 'false',
                'annotations' => 'false',
            ]);

            if (! $response->ok()) {
                return new RouteEtaResult(0, 0, 'osrm', false, 'osrm_http_'.$response->status(), [
                    'status' => $response->status(),
                ]);
            }

            $json = $response->json();
            $route = data_get($json, 'routes.0');
            if (! is_array($route)) {
                return new RouteEtaResult(0, 0, 'osrm', false, 'osrm_no_route', [
                    'raw' => $json,
                ]);
            }

            return new RouteEtaResult(
                etaSeconds: (int) round((float) data_get($route, 'duration', 0)),
                distanceMeters: (int) round((float) data_get($route, 'distance', 0)),
                provider: 'osrm',
                success: true,
                error: null,
                raw: [
                    'code' => data_get($json, 'code'),
                    'waypoints' => data_get($json, 'waypoints'),
                ],
            );
        } catch (\Throwable $exception) {
            return new RouteEtaResult(
                etaSeconds: 0,
                distanceMeters: 0,
                provider: 'osrm',
                success: false,
                error: 'osrm_exception',
                raw: [
                    'message' => $exception->getMessage(),
                ],
            );
        }
    }

    public function matrix(array $sources, array $destinations, array $context = []): array
    {
        $baseUrl = rtrim((string) config('routing.osrm.base_url', ''), '/');
        if ($baseUrl === '' || $sources === [] || $destinations === []) {
            return $this->fallbackMatrix($sources, $destinations, 'osrm_matrix_unavailable');
        }

        $timeout = max(1, (int) config('routing.osrm.timeout_seconds', 3));
        $all = array_values(array_merge($sources, $destinations));
        $coord = implode(';', array_map(
            static fn (RouteLocation $point): string => $point->lng.','.$point->lat,
            $all
        ));
        $sourceIndexes = implode(';', array_map('strval', array_keys($sources)));
        $destinationOffset = count($sources);
        $destinationIndexes = implode(';', array_map(
            static fn (int $idx): string => (string) ($destinationOffset + $idx),
            array_keys($destinations)
        ));

        try {
            $response = Http::timeout($timeout)->get($baseUrl.'/table/v1/driving/'.$coord, [
                'annotations' => 'duration,distance',
                'sources' => $sourceIndexes,
                'destinations' => $destinationIndexes,
            ]);

            if (! $response->ok()) {
                return $this->fallbackMatrix($sources, $destinations, 'osrm_matrix_http_'.$response->status());
            }

            $json = $response->json();
            $durations = (array) data_get($json, 'durations', []);
            $distances = (array) data_get($json, 'distances', []);

            $rows = [];
            foreach ($sources as $sIndex => $source) {
                $rows[$sIndex] = [];
                foreach ($destinations as $dIndex => $destination) {
                    $duration = data_get($durations, $sIndex.'.'.$dIndex);
                    $distance = data_get($distances, $sIndex.'.'.$dIndex);
                    if (! is_numeric($duration) || ! is_numeric($distance)) {
                        $rows[$sIndex][$dIndex] = new RouteEtaResult(0, 0, 'osrm', false, 'osrm_matrix_cell_missing');
                        continue;
                    }
                    $rows[$sIndex][$dIndex] = new RouteEtaResult(
                        etaSeconds: (int) round((float) $duration),
                        distanceMeters: (int) round((float) $distance),
                        provider: 'osrm',
                        success: true,
                        error: null,
                    );
                }
            }

            return $rows;
        } catch (\Throwable $exception) {
            return $this->fallbackMatrix($sources, $destinations, 'osrm_matrix_exception', [
                'message' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * @param RouteLocation[] $sources
     * @param RouteLocation[] $destinations
     */
    private function fallbackMatrix(array $sources, array $destinations, string $error, array $raw = []): array
    {
        $rows = [];
        foreach ($sources as $sIndex => $source) {
            $rows[$sIndex] = [];
            foreach ($destinations as $dIndex => $destination) {
                $rows[$sIndex][$dIndex] = new RouteEtaResult(0, 0, 'osrm', false, $error, $raw);
            }
        }

        return $rows;
    }
}

