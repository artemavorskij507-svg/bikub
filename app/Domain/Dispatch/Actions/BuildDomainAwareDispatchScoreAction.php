<?php

namespace App\Domain\Dispatch\Actions;

use App\Domain\Roadside\Actions\ApplyEmergencyPriorityOverrideAction;
use App\Models\Operations\Executor;
use App\Models\Operations\ServiceJob;
use App\Models\Operations\Assignment;
use Illuminate\Support\Facades\Redis;

class BuildDomainAwareDispatchScoreAction
{
    public function __construct(
        private readonly ResolveRuntimeDispatchRuleSetAction $resolveRuntimeDispatchRuleSetAction,
        private readonly ComputeDomainPriorityModifierAction $computeDomainPriorityModifierAction,
        private readonly ComputeLoadModifierAction $computeLoadModifierAction,
        private readonly ApplyEmergencyPriorityOverrideAction $applyEmergencyPriorityOverrideAction,
    ) {}

    public function execute(
        ServiceJob $job,
        Executor $executor,
        int $etaSeconds,
        array $checks,
    ): array {
        $runtimeRules = $this->resolveRuntimeDispatchRuleSetAction->execute($job);
        $weights = (array) data_get($runtimeRules, 'weights', []);
        $modifiersConfig = (array) data_get($runtimeRules, 'modifiers', []);

        $distanceKm = $this->distanceKm($job, $executor);
        $proximityScore = $distanceKm === null ? 30 : max(0, 100 - (int) round($distanceKm * 8));
        $etaScore = max(0, 100 - (int) round($etaSeconds / 30));
        $freshPingScore = $this->freshPingScore($executor);
        $activeJobsCount = Assignment::query()
            ->where('executor_id', $executor->id)
            ->whereIn('status', ['proposed', 'offered', 'accepted', 'active'])
            ->count();
        $loadScore = max(0, 100 - ($activeJobsCount * 25));
        $shiftScore = data_get($checks, 'shift.eligible') ? 100 : 0;
        $timeWindowScore = data_get($checks, 'time_window.fits') ? match (data_get($checks, 'time_window.risk')) {
            'high' => 50,
            'medium' => 75,
            default => 100,
        } : 0;
        $capacityScore = data_get($checks, 'capacity.fits') ? 100 : 0;

        $baseRaw = (
            $proximityScore * ($weights['proximity'] ?? 0.2) +
            $etaScore * ($weights['eta'] ?? 0.2) +
            $freshPingScore * ($weights['fresh_ping'] ?? 0.1) +
            $loadScore * ($weights['load'] ?? 0.1) +
            $shiftScore * ($weights['shift_fit'] ?? 0.15) +
            $timeWindowScore * ($weights['time_window_fit'] ?? 0.15) +
            $capacityScore * ($weights['capacity_fit'] ?? 0.2)
        );

        $timeWindowPenalty = match (data_get($checks, 'time_window.risk')) {
            'high' => (float) ($modifiersConfig['window_high_risk_penalty'] ?? -12),
            'medium' => (float) ($modifiersConfig['window_medium_risk_penalty'] ?? -5),
            default => 0.0,
        };
        $domainPriority = $this->computeDomainPriorityModifierAction->execute($job, $runtimeRules);
        $loadModifier = $this->computeLoadModifierAction->execute($activeJobsCount, $runtimeRules);

        $modified = $baseRaw + $timeWindowPenalty + (float) $domainPriority['modifier'] + (float) $loadModifier['modifier'];
        $final = $this->applyEmergencyPriorityOverrideAction->execute($job, $modified, $runtimeRules);
        $emergencyOverrideApplied = round($final - $modified, 2);

        return [
            'score' => round(max(0, min(100, $final)), 2),
            'breakdown' => [
                'base' => [
                    'proximity' => $proximityScore,
                    'eta' => $etaScore,
                    'fresh_ping' => $freshPingScore,
                    'load' => $loadScore,
                    'distance_km' => $distanceKm,
                    'eta_seconds' => $etaSeconds,
                    'active_jobs_count' => $activeJobsCount,
                ],
                'weighted' => [
                    'weights' => $weights,
                    'base_score' => round($baseRaw, 2),
                ],
                'modifiers' => [
                    'time_window_risk_penalty' => [
                        'modifier' => round($timeWindowPenalty, 2),
                        'reason' => 'time_window_risk',
                    ],
                    'domain_priority' => $domainPriority,
                    'load_modifier' => $loadModifier,
                    'roadside_emergency_override' => [
                        'modifier' => $emergencyOverrideApplied,
                        'reason' => 'roadside_emergency_override',
                    ],
                ],
                'shift_fit' => data_get($checks, 'shift'),
                'time_window_fit' => data_get($checks, 'time_window'),
                'capacity_fit' => data_get($checks, 'capacity'),
                'runtime_rule_set' => data_get($runtimeRules, 'rule_set'),
            ],
        ];
    }

    private function freshPingScore(Executor $executor): int
    {
        $lastSeenRaw = Redis::get("executor:{$executor->id}:last_seen_at");
        if (! $lastSeenRaw) {
            return 40;
        }
        $lastSeen = is_numeric($lastSeenRaw)
            ? now()->createFromTimestamp((int) $lastSeenRaw)
            : now()->parse((string) $lastSeenRaw);

        $minutes = $lastSeen->diffInMinutes(now());

        return match (true) {
            $minutes <= 2 => 100,
            $minutes <= 5 => 80,
            $minutes <= 10 => 55,
            default => 25,
        };
    }

    private function distanceKm(ServiceJob $job, Executor $executor): ?float
    {
        $target = null;
        if ($job->service_lat && $job->service_lng) {
            $target = [(float) $job->service_lat, (float) $job->service_lng];
        } elseif ($job->pickup_lat && $job->pickup_lng) {
            $target = [(float) $job->pickup_lat, (float) $job->pickup_lng];
        }

        if (! $target) {
            return null;
        }

        $locationRaw = Redis::get("executor:{$executor->id}:last_location");
        $location = $locationRaw ? json_decode($locationRaw, true) : null;
        if (! $location || empty($location['latitude']) || empty($location['longitude'])) {
            return null;
        }

        $lat1 = $target[0];
        $lng1 = $target[1];
        $lat2 = (float) $location['latitude'];
        $lng2 = (float) $location['longitude'];

        $earthRadiusKm = 6371.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return round($earthRadiusKm * (2 * atan2(sqrt($a), sqrt(1 - $a))), 3);
    }
}
