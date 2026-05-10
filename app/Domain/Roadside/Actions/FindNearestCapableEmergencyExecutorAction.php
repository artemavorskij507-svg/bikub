<?php

namespace App\Domain\Roadside\Actions;

use App\Domain\Dispatch\Actions\CheckCapacityFitAction;
use App\Domain\Dispatch\Actions\CheckExecutorShiftEligibilityAction;
use App\Models\Operations\Executor;
use App\Models\Operations\ServiceJob;
use Illuminate\Support\Facades\Redis;

class FindNearestCapableEmergencyExecutorAction
{
    public function __construct(
        private readonly CheckExecutorShiftEligibilityAction $checkExecutorShiftEligibilityAction,
        private readonly CheckCapacityFitAction $checkCapacityFitAction,
    ) {}

    public function execute(ServiceJob $job): ?array
    {
        $target = $this->jobPoint($job);
        if (! $target) {
            return null;
        }

        $executors = Executor::query()
            ->where('organization_id', $job->organization_id)
            ->where('is_dispatchable', true)
            ->whereIn('status', ['available', 'busy'])
            ->get();

        $best = null;
        foreach ($executors as $executor) {
            $shift = $this->checkExecutorShiftEligibilityAction->execute($job, $executor);
            if (! $shift['eligible']) {
                continue;
            }

            $cap = $this->checkCapacityFitAction->execute($job, $executor);
            if (! $cap['fits']) {
                continue;
            }

            $locationRaw = Redis::get("executor:{$executor->id}:last_location");
            $location = $locationRaw ? json_decode($locationRaw, true) : null;
            if (! $location || empty($location['latitude']) || empty($location['longitude'])) {
                continue;
            }

            $distance = $this->haversineKm($target['lat'], $target['lng'], (float) $location['latitude'], (float) $location['longitude']);
            if ($best === null || $distance < $best['distance_km']) {
                $best = [
                    'executor_id' => $executor->id,
                    'distance_km' => round($distance, 2),
                ];
            }
        }

        return $best;
    }

    private function jobPoint(ServiceJob $job): ?array
    {
        if ($job->service_lat && $job->service_lng) {
            return ['lat' => (float) $job->service_lat, 'lng' => (float) $job->service_lng];
        }
        if ($job->pickup_lat && $job->pickup_lng) {
            return ['lat' => (float) $job->pickup_lat, 'lng' => (float) $job->pickup_lng];
        }

        return null;
    }

    private function haversineKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadiusKm = 6371.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        return $earthRadiusKm * (2 * atan2(sqrt($a), sqrt(1 - $a)));
    }
}

