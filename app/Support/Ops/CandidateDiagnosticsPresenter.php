<?php

namespace App\Support\Ops;

use App\Domain\Moving\Models\TeamAssignment;
use App\Models\Operations\DispatchCandidate;

class CandidateDiagnosticsPresenter
{
    public static function fromDispatchCandidate(
        DispatchCandidate $candidate,
        ?int $selectedExecutorId,
        array $runtimeRules,
        string $serviceDomain,
        ?TeamAssignment $movingTeamAssignment = null,
        bool $roadsideEmergency = false,
    ): array {
        $breakdown = (array) ($candidate->score_breakdown ?? []);
        $modifiersRaw = (array) data_get($breakdown, 'modifiers', []);
        $modifiers = DispatchModifierPresenter::presentAll($modifiersRaw);
        $reasons = array_values((array) ($candidate->ineligibility_reasons ?? []));
        $primaryReason = $reasons[0] ?? (string) data_get($breakdown, 'rejection_reason_primary', '');
        $distanceKm = data_get($breakdown, 'base.distance_km');
        $etaSeconds = data_get($breakdown, 'base.eta_seconds');
        $routing = (array) data_get($breakdown, 'routing', []);

        $movingMemberIds = (array) ($movingTeamAssignment?->member_executor_ids_json ?? []);
        $isMovingMember = in_array((int) $candidate->executor_id, array_map('intval', $movingMemberIds), true);

        return [
            'executor_id' => (int) $candidate->executor_id,
            'executor_name' => $candidate->executor?->display_name ?: $candidate->executor?->name ?: ('Executor #' . $candidate->executor_id),
            'display_name' => $candidate->executor?->display_name ?: $candidate->executor?->name ?: ('Executor #' . $candidate->executor_id),
            'status' => (string) ($candidate->executor?->status ?? ''),
            'vehicle_type' => $candidate->executor?->vehicle_type,
            'is_eligible' => (bool) $candidate->eligible,
            'eligible' => (bool) $candidate->eligible,
            'rejection_reason' => $primaryReason ?: null,
            'rejection_reason_label' => $primaryReason !== '' ? DispatchReasonPresenter::label($primaryReason) : null,
            'ineligibility_reasons' => $reasons,
            'ineligibility_reason_labels' => array_map(
                static fn (string $reason): string => DispatchReasonPresenter::label($reason),
                $reasons
            ),
            'score_total' => (float) $candidate->score,
            'score' => (float) $candidate->score,
            'selected' => $selectedExecutorId !== null && (int) $candidate->executor_id === (int) $selectedExecutorId,
            'distance_meters' => is_numeric($distanceKm) ? (int) round(((float) $distanceKm) * 1000) : null,
            'distance_km' => is_numeric($distanceKm) ? round((float) $distanceKm, 2) : null,
            'eta_seconds' => is_numeric($etaSeconds) ? (int) $etaSeconds : null,
            'checks' => [
                'shift_fit' => (array) data_get($breakdown, 'shift_fit', data_get($breakdown, 'shift', [])),
                'time_window_fit' => (array) data_get($breakdown, 'time_window_fit', data_get($breakdown, 'time_window', [])),
                'capacity_fit' => (array) data_get($breakdown, 'capacity_fit', data_get($breakdown, 'capacity', [])),
            ],
            'shift_fit' => (array) data_get($breakdown, 'shift_fit', data_get($breakdown, 'shift', [])),
            'time_window_fit' => (array) data_get($breakdown, 'time_window_fit', data_get($breakdown, 'time_window', [])),
            'capacity_fit' => (array) data_get($breakdown, 'capacity_fit', data_get($breakdown, 'capacity', [])),
            'scoring' => [
                'base' => (array) data_get($breakdown, 'base', []),
                'weighted' => (array) data_get($breakdown, 'weighted', []),
                'modifiers' => $modifiers,
            ],
            'base_score' => (float) data_get($breakdown, 'weighted.base_score', 0),
            'weighted_score' => (float) data_get($breakdown, 'weighted.base_score', 0),
            'modifiers' => $modifiers,
            'runtime' => [
                'effective_rule_values' => self::effectiveRuntimeRuleValues($runtimeRules, $breakdown),
            ],
            'score_breakdown' => $breakdown,
            'routing' => [
                'heuristic_eta_seconds' => data_get($routing, 'heuristic_eta_seconds'),
                'routing_eta_seconds' => data_get($routing, 'routing_eta_seconds'),
                'eta_delta_seconds' => data_get($routing, 'eta_delta_seconds'),
                'heuristic_distance_meters' => data_get($routing, 'heuristic_distance_meters'),
                'routing_distance_meters' => data_get($routing, 'routing_distance_meters'),
                'distance_delta_meters' => data_get($routing, 'distance_delta_meters'),
                'delta_percent' => data_get($routing, 'delta_percent'),
                'significance' => data_get($routing, 'significance', 'unavailable'),
                'routing_available' => (bool) data_get($routing, 'routing_available', false),
                'routing_error' => data_get($routing, 'routing_error'),
                'provider' => data_get($routing, 'provider'),
                'would_change_ranking' => (bool) data_get($routing, 'would_change_ranking', false),
                'shadow_only' => (bool) data_get($routing, 'shadow_only', true),
            ],
            'special_hints' => [
                'roadside_emergency_override_applied' => $roadsideEmergency
                    && ((float) data_get($modifiersRaw, 'roadside_emergency_override.modifier', 0) > 0),
                'moving_team_candidate' => $serviceDomain === 'moving' && $isMovingMember,
                'moving_team_eta_seconds' => self::movingTeamEtaSeconds($movingTeamAssignment),
            ],
        ];
    }

    public static function fromFallbackCandidate(
        array $candidate,
        ?int $selectedExecutorId,
        array $runtimeRules,
        string $serviceDomain,
        ?TeamAssignment $movingTeamAssignment = null,
        bool $roadsideEmergency = false,
    ): array {
        $executorId = (int) data_get($candidate, 'executor_id');
        $distanceKm = data_get($candidate, 'distance_km');
        $movingMemberIds = (array) ($movingTeamAssignment?->member_executor_ids_json ?? []);

        return [
            'executor_id' => $executorId,
            'executor_name' => (string) data_get($candidate, 'display_name', 'Executor #' . $executorId),
            'display_name' => (string) data_get($candidate, 'display_name', 'Executor #' . $executorId),
            'status' => (string) data_get($candidate, 'status', ''),
            'vehicle_type' => data_get($candidate, 'vehicle_type'),
            'is_eligible' => true,
            'eligible' => true,
            'rejection_reason' => null,
            'rejection_reason_label' => null,
            'ineligibility_reasons' => [],
            'ineligibility_reason_labels' => [],
            'score_total' => (float) data_get($candidate, 'score', 0),
            'score' => (float) data_get($candidate, 'score', 0),
            'selected' => $selectedExecutorId !== null && $executorId === (int) $selectedExecutorId,
            'distance_meters' => is_numeric($distanceKm) ? (int) round(((float) $distanceKm) * 1000) : null,
            'distance_km' => is_numeric($distanceKm) ? round((float) $distanceKm, 2) : null,
            'eta_seconds' => null,
            'checks' => [
                'shift_fit' => [],
                'time_window_fit' => [],
                'capacity_fit' => [],
            ],
            'shift_fit' => [],
            'time_window_fit' => [],
            'capacity_fit' => [],
            'scoring' => [
                'base' => [],
                'weighted' => [],
                'modifiers' => [],
            ],
            'base_score' => (float) data_get($candidate, 'score', 0),
            'weighted_score' => (float) data_get($candidate, 'score', 0),
            'modifiers' => [],
            'runtime' => [
                'effective_rule_values' => self::effectiveRuntimeRuleValues($runtimeRules, []),
            ],
            'score_breakdown' => [],
            'routing' => [
                'heuristic_eta_seconds' => null,
                'routing_eta_seconds' => null,
                'eta_delta_seconds' => null,
                'heuristic_distance_meters' => null,
                'routing_distance_meters' => null,
                'distance_delta_meters' => null,
                'delta_percent' => null,
                'significance' => 'unavailable',
                'routing_available' => false,
                'routing_error' => 'routing_not_calculated',
                'provider' => null,
                'would_change_ranking' => false,
                'shadow_only' => true,
            ],
            'special_hints' => [
                'roadside_emergency_override_applied' => $roadsideEmergency,
                'moving_team_candidate' => $serviceDomain === 'moving'
                    && in_array($executorId, array_map('intval', $movingMemberIds), true),
                'moving_team_eta_seconds' => self::movingTeamEtaSeconds($movingTeamAssignment),
            ],
        ];
    }

    public static function effectiveRuntimeRuleValues(array $runtimeRules, array $scoreBreakdown = []): array
    {
        return [
            'weights' => [
                'eta' => (float) data_get($scoreBreakdown, 'weighted.weights.eta', data_get($runtimeRules, 'weights.eta', 0)),
                'time_window_fit' => (float) data_get($scoreBreakdown, 'weighted.weights.time_window_fit', data_get($runtimeRules, 'weights.time_window_fit', 0)),
                'capacity_fit' => (float) data_get($scoreBreakdown, 'weighted.weights.capacity_fit', data_get($runtimeRules, 'weights.capacity_fit', 0)),
            ],
            'modifiers' => [
                'emergency_boost' => (float) data_get($runtimeRules, 'modifiers.emergency_boost', 0),
                'window_high_risk_penalty' => (float) data_get($runtimeRules, 'modifiers.window_high_risk_penalty', 0),
                'window_medium_risk_penalty' => (float) data_get($runtimeRules, 'modifiers.window_medium_risk_penalty', 0),
                'domain_priority_boost' => (float) data_get($runtimeRules, 'modifiers.domain_priority_boost', 0),
                'load_penalty_scale' => (float) data_get($runtimeRules, 'modifiers.load_penalty_scale', 0),
            ],
            'roadside' => [
                'acceptance_timeout_seconds' => (int) data_get($runtimeRules, 'roadside.acceptance_timeout_seconds', 0),
            ],
            'moving' => [
                'default_required_team_size' => (int) data_get($runtimeRules, 'moving.default_required_team_size', 0),
            ],
            'rule_set' => (string) data_get($runtimeRules, 'rule_set', ''),
        ];
    }

    private static function movingTeamEtaSeconds(?TeamAssignment $movingTeamAssignment): ?int
    {
        if (! $movingTeamAssignment?->eta_at) {
            return null;
        }

        return max(0, now()->diffInSeconds($movingTeamAssignment->eta_at, false));
    }
}
