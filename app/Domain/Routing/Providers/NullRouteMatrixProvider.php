<?php

namespace App\Domain\Routing\Providers;

use App\Domain\Routing\Contracts\RouteMatrixProvider;
use App\Domain\Routing\DTO\RouteEtaResult;
use App\Domain\Routing\DTO\RouteLocation;

class NullRouteMatrixProvider implements RouteMatrixProvider
{
    public function estimateEta(RouteLocation $from, RouteLocation $to, array $context = []): RouteEtaResult
    {
        return new RouteEtaResult(
            etaSeconds: 0,
            distanceMeters: 0,
            provider: 'null',
            success: false,
            error: 'routing_provider_unavailable',
            raw: [
                'context' => $context,
            ],
        );
    }

    public function matrix(array $sources, array $destinations, array $context = []): array
    {
        $rows = [];
        foreach ($sources as $sourceIndex => $source) {
            $rows[$sourceIndex] = [];
            foreach ($destinations as $destinationIndex => $destination) {
                $rows[$sourceIndex][$destinationIndex] = $this->estimateEta($source, $destination, $context);
            }
        }

        return $rows;
    }
}

