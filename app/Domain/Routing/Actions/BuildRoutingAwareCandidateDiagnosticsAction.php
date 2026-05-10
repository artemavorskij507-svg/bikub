<?php

namespace App\Domain\Routing\Actions;

class BuildRoutingAwareCandidateDiagnosticsAction
{
    public function execute(array $etaCompare, bool $shadowOnly = true): array
    {
        $deltaPercent = data_get($etaCompare, 'delta_percent');
        $wouldChangeRanking = abs((float) ($deltaPercent ?? 0)) > 10.0;

        return [
            'heuristic_eta_seconds' => data_get($etaCompare, 'heuristic_eta_seconds'),
            'routing_eta_seconds' => data_get($etaCompare, 'routing_eta_seconds'),
            'eta_delta_seconds' => data_get($etaCompare, 'eta_delta_seconds'),
            'heuristic_distance_meters' => data_get($etaCompare, 'heuristic_distance_meters'),
            'routing_distance_meters' => data_get($etaCompare, 'routing_distance_meters'),
            'distance_delta_meters' => data_get($etaCompare, 'distance_delta_meters'),
            'delta_percent' => $deltaPercent,
            'significance' => data_get($etaCompare, 'significance', 'unavailable'),
            'routing_available' => (bool) data_get($etaCompare, 'routing_available', false),
            'routing_error' => data_get($etaCompare, 'routing_error'),
            'provider' => data_get($etaCompare, 'provider'),
            'would_change_ranking' => $wouldChangeRanking,
            'shadow_only' => $shadowOnly,
        ];
    }
}

