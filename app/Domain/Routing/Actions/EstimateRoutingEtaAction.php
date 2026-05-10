<?php

namespace App\Domain\Routing\Actions;

use App\Domain\Operations\Models\ServiceJob;
use App\Domain\Routing\Contracts\RouteMatrixProvider;
use App\Domain\Routing\DTO\RouteEtaResult;
use App\Domain\Routing\DTO\RouteLocation;
use App\Models\Operations\Executor;
use Illuminate\Support\Facades\Redis;

class EstimateRoutingEtaAction
{
    public function __construct(
        private readonly RouteMatrixProvider $routeMatrixProvider,
    ) {}

    public function execute(ServiceJob $job, Executor $executor): RouteEtaResult
    {
        if (! (bool) config('routing.shadow_mode', true)) {
            return new RouteEtaResult(0, 0, 'shadow_disabled', false, 'routing_shadow_mode_disabled');
        }

        $from = $this->executorLocation($executor);
        $to = $this->jobLocation($job);
        if (! $from || ! $to) {
            return new RouteEtaResult(0, 0, 'internal', false, 'routing_missing_coordinates');
        }

        return $this->routeMatrixProvider->estimateEta($from, $to, [
            'service_job_id' => $job->id,
            'executor_id' => $executor->id,
            'service_domain' => $job->service_domain,
            'job_kind' => $job->job_kind,
        ]);
    }

    private function executorLocation(Executor $executor): ?RouteLocation
    {
        $raw = Redis::get("executor:{$executor->id}:last_location");
        $location = $raw ? json_decode($raw, true) : null;
        $lat = (float) data_get($location, 'latitude', 0);
        $lng = (float) data_get($location, 'longitude', 0);
        if ($lat === 0.0 && $lng === 0.0) {
            return null;
        }

        return new RouteLocation($lat, $lng, 'executor#'.$executor->id);
    }

    private function jobLocation(ServiceJob $job): ?RouteLocation
    {
        $lat = (float) ($job->service_lat ?: $job->pickup_lat ?: 0);
        $lng = (float) ($job->service_lng ?: $job->pickup_lng ?: 0);
        if ($lat === 0.0 && $lng === 0.0) {
            return null;
        }

        return new RouteLocation($lat, $lng, 'job#'.$job->id);
    }
}
