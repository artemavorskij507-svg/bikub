<?php

namespace App\Domain\Routing\Actions;

use App\Domain\Routing\DTO\RouteEtaResult;

class CompareEtaStrategiesAction
{
    public function execute(
        ?int $heuristicEtaSeconds,
        ?int $heuristicDistanceMeters,
        ?RouteEtaResult $routingResult,
    ): array {
        $heuristicEta = $heuristicEtaSeconds && $heuristicEtaSeconds > 0 ? (int) $heuristicEtaSeconds : null;
        $heuristicDistance = $heuristicDistanceMeters && $heuristicDistanceMeters > 0 ? (int) $heuristicDistanceMeters : null;

        if (! $routingResult || ! $routingResult->success) {
            return [
                'heuristic_eta_seconds' => $heuristicEta,
                'routing_eta_seconds' => null,
                'eta_delta_seconds' => null,
                'heuristic_distance_meters' => $heuristicDistance,
                'routing_distance_meters' => null,
                'distance_delta_meters' => null,
                'delta_percent' => null,
                'significance' => 'unavailable',
                'routing_available' => false,
                'routing_error' => $routingResult?->error,
                'provider' => $routingResult?->provider,
            ];
        }

        $routingEta = $routingResult->etaSeconds > 0 ? $routingResult->etaSeconds : null;
        $routingDistance = $routingResult->distanceMeters > 0 ? $routingResult->distanceMeters : null;
        $etaDelta = ($routingEta !== null && $heuristicEta !== null) ? ($routingEta - $heuristicEta) : null;
        $distanceDelta = ($routingDistance !== null && $heuristicDistance !== null) ? ($routingDistance - $heuristicDistance) : null;
        $deltaPercent = ($heuristicEta !== null && $heuristicEta > 0 && $etaDelta !== null)
            ? round(($etaDelta / $heuristicEta) * 100, 2)
            : null;

        $magnitude = abs((float) ($deltaPercent ?? 0));
        $significance = match (true) {
            $deltaPercent === null => 'unavailable',
            $magnitude < 5 => 'low',
            $magnitude <= 15 => 'medium',
            default => 'high',
        };

        return [
            'heuristic_eta_seconds' => $heuristicEta,
            'routing_eta_seconds' => $routingEta,
            'eta_delta_seconds' => $etaDelta,
            'heuristic_distance_meters' => $heuristicDistance,
            'routing_distance_meters' => $routingDistance,
            'distance_delta_meters' => $distanceDelta,
            'delta_percent' => $deltaPercent,
            'significance' => $significance,
            'routing_available' => true,
            'routing_error' => null,
            'provider' => $routingResult->provider,
        ];
    }
}

