<?php

namespace App\Domain\Ops\Queries;

use App\Models\Operations\Executor;
use Illuminate\Support\Facades\Redis;

class DispatchCandidatesQuery
{
    public function execute(string $organizationId, float|int|null $lat = null, float|int|null $lng = null, int $limit = 10): array
    {
        $executors = Executor::query()
            ->where('organization_id', (string) $organizationId)
            ->whereIn('status', ['available', 'busy', 'online', 'idle'])
            ->where(function ($q): void {
                $q->whereNull('is_dispatchable')->orWhere('is_dispatchable', true);
            })
            ->limit(200)
            ->get(['id', 'display_name', 'name', 'status', 'vehicle_type', 'skills', 'equipment']);

        $candidates = [];
        foreach ($executors as $executor) {
            $lastLocationRaw = Redis::get("executor:{$executor->id}:last_location");
            $lastLocation = $lastLocationRaw ? json_decode($lastLocationRaw, true) : null;

            $distanceKm = null;
            if ($lat !== null && $lng !== null && ! empty($lastLocation['latitude']) && ! empty($lastLocation['longitude'])) {
                $distanceKm = $this->haversineKm(
                    (float) $lat,
                    (float) $lng,
                    (float) $lastLocation['latitude'],
                    (float) $lastLocation['longitude'],
                );
            }

            $status = $this->normalizeExecutorStatus((string) $executor->status);
            $statusScore = match ($status) {
                'available' => 100,
                'busy' => 60,
                default => 30,
            };

            $distanceScore = $distanceKm === null ? 40 : max(0, 100 - (int) round($distanceKm * 8));
            $score = (int) round(($statusScore * 0.6) + ($distanceScore * 0.4));

            $candidates[] = [
                'executor_id' => $executor->id,
                'display_name' => $executor->display_name ?: $executor->name ?: ('Executor #'.$executor->id),
                'status' => $status,
                'vehicle_type' => $executor->vehicle_type,
                'distance_km' => $distanceKm !== null ? round($distanceKm, 2) : null,
                'score' => $score,
                'last_location' => $lastLocation,
            ];
        }

        usort($candidates, function (array $a, array $b): int {
            if ($a['score'] !== $b['score']) {
                return $b['score'] <=> $a['score'];
            }

            return ($a['distance_km'] ?? 9999) <=> ($b['distance_km'] ?? 9999);
        });

        return array_slice($candidates, 0, max(1, $limit));
    }

    private function haversineKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadiusKm = 6371.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2)
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
            * sin($dLng / 2) * sin($dLng / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadiusKm * $c;
    }

    private function normalizeExecutorStatus(string $status): string
    {
        $status = strtolower(trim($status));

        return match ($status) {
            'online', 'idle' => 'available',
            'available', 'busy', 'offline', 'paused', 'suspended' => $status,
            default => 'available',
        };
    }
}
