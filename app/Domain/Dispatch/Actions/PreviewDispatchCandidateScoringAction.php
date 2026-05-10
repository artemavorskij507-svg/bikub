<?php

namespace App\Domain\Dispatch\Actions;

use App\Domain\Roadside\Actions\ApplyEmergencyPriorityOverrideAction;
use App\Models\Operations\Assignment;
use App\Models\Operations\Executor;
use App\Models\Operations\ServiceJob;
use Illuminate\Support\Facades\Redis;

class PreviewDispatchCandidateScoringAction
{
    public function __construct(
        private readonly ResolveRuntimeDispatchRuleSetAction $resolveRuntimeDispatchRuleSetAction,
        private readonly CheckExecutorShiftEligibilityAction $checkExecutorShiftEligibilityAction,
        private readonly CheckTimeWindowFitAction $checkTimeWindowFitAction,
        private readonly CheckCapacityFitAction $checkCapacityFitAction,
        private readonly ComputeDomainPriorityModifierAction $computeDomainPriorityModifierAction,
        private readonly ComputeLoadModifierAction $computeLoadModifierAction,
        private readonly ApplyEmergencyPriorityOverrideAction $applyEmergencyPriorityOverrideAction,
    ) {}

    public function execute(ServiceJob $job, array $override = []): array
    {
        $oldRules = $this->resolveRuntimeDispatchRuleSetAction->execute($job);
        $newRules = $oldRules;
        if (! empty($override['rule_key'])) {
            $this->setByPath($newRules, (string) $override['rule_key'], $override['value'] ?? null);
        }

        $executors = Executor::query()
            ->where('organization_id', $job->organization_id)
            ->where('is_dispatchable', true)
            ->whereIn('status', ['available', 'busy'])
            ->limit(8)
            ->get();

        $rows = [];
        foreach ($executors as $executor) {
            $distanceKm = $this->distanceKm($job, $executor);
            $etaSeconds = $this->estimateEtaSeconds($distanceKm);
            $shift = $this->checkExecutorShiftEligibilityAction->execute($job, $executor, $etaSeconds);
            $timeWindow = $this->checkTimeWindowFitAction->execute($job, $etaSeconds);
            $capacity = $this->checkCapacityFitAction->execute($job, $executor);
            $checks = ['shift' => $shift, 'time_window' => $timeWindow, 'capacity' => $capacity];

            $old = $this->scoreWithRules($job, $executor, $etaSeconds, $checks, $oldRules);
            $new = $this->scoreWithRules($job, $executor, $etaSeconds, $checks, $newRules);

            $eligible = (bool) ($shift['eligible'] && $timeWindow['fits'] && $capacity['fits']);
            $reasons = array_values(array_filter([
                $shift['eligible'] ? null : ($shift['reason'] ?? 'shift_ineligible'),
                $timeWindow['fits'] ? null : 'time_window_miss',
                $capacity['fits'] ? null : ($capacity['reason'] ?? 'capacity_mismatch'),
            ]));

            $rows[] = [
                'executor_id' => $executor->id,
                'executor_name' => $executor->display_name ?: $executor->name,
                'eligible' => $eligible,
                'rejection_reason' => $reasons[0] ?? null,
                'old_score' => $old['score'],
                'new_score' => $new['score'],
                'delta' => round($new['score'] - $old['score'], 2),
                'base_score' => data_get($new, 'breakdown.weighted.base_score'),
                'weighted_score' => data_get($new, 'breakdown.weighted.base_score'),
                'modifiers' => $new['breakdown']['modifiers'],
                'shift_fit' => data_get($new, 'breakdown.shift_fit'),
                'time_window_fit' => data_get($new, 'breakdown.time_window_fit'),
                'capacity_fit' => data_get($new, 'breakdown.capacity_fit'),
                'selected' => false,
            ];
        }

        usort($rows, fn (array $a, array $b) => $b['new_score'] <=> $a['new_score']);
        foreach ($rows as $i => $row) {
            if ($i === 0 && $row['eligible']) {
                $rows[$i]['selected'] = true;
                break;
            }
        }

        return ['old_rules' => $oldRules, 'new_rules' => $newRules, 'rows' => $rows];
    }

    private function scoreWithRules(ServiceJob $job, Executor $executor, int $etaSeconds, array $checks, array $runtimeRules): array
    {
        $weights = (array) data_get($runtimeRules, 'weights', []);
        $modifiersConfig = (array) data_get($runtimeRules, 'modifiers', []);
        $distanceKm = $this->distanceKm($job, $executor);

        $proximityScore = $distanceKm === null ? 30 : max(0, 100 - (int) round($distanceKm * 8));
        $etaScore = max(0, 100 - (int) round($etaSeconds / 30));
        $freshPingScore = $this->freshPingScore($executor);
        $activeJobsCount = Assignment::query()->where('executor_id', $executor->id)->whereIn('status', ['proposed', 'offered', 'accepted', 'active'])->count();
        $loadScore = max(0, 100 - ($activeJobsCount * 25));
        $shiftScore = data_get($checks, 'shift.eligible') ? 100 : 0;
        $timeWindowScore = data_get($checks, 'time_window.fits') ? match (data_get($checks, 'time_window.risk')) {
            'high' => 50, 'medium' => 75, default => 100,
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

        return [
            'score' => round(max(0, min(100, $final)), 2),
            'breakdown' => [
                'modifiers' => [
                    'time_window_risk_penalty' => ['modifier' => round($timeWindowPenalty, 2), 'reason' => 'time_window_risk'],
                    'domain_priority' => $domainPriority,
                    'load_modifier' => $loadModifier,
                    'roadside_emergency_override' => ['modifier' => round($final - $modified, 2), 'reason' => 'roadside_emergency_override'],
                ],
            ],
        ];
    }

    private function setByPath(array &$target, string $path, mixed $value): void
    {
        $segments = array_filter(explode('.', trim($path)), fn ($segment) => $segment !== '');
        if ($segments === []) return;
        $cursor = &$target;
        foreach ($segments as $index => $segment) {
            if ($index === count($segments) - 1) { $cursor[$segment] = $value; return; }
            if (! isset($cursor[$segment]) || ! is_array($cursor[$segment])) { $cursor[$segment] = []; }
            $cursor = &$cursor[$segment];
        }
    }

    private function estimateEtaSeconds(?float $distanceKm): int
    {
        if ($distanceKm === null) return 20 * 60;
        $avgSpeedKmh = 35.0;
        return (int) max(180, round(($distanceKm / $avgSpeedKmh) * 3600));
    }

    private function distanceKm(ServiceJob $job, Executor $executor): ?float
    {
        $target = null;
        if ($job->service_lat && $job->service_lng) $target = [(float) $job->service_lat, (float) $job->service_lng];
        elseif ($job->pickup_lat && $job->pickup_lng) $target = [(float) $job->pickup_lat, (float) $job->pickup_lng];
        if (! $target) return null;

        $locationRaw = Redis::get("executor:{$executor->id}:last_location");
        $location = $locationRaw ? json_decode($locationRaw, true) : null;
        if (! $location || empty($location['latitude']) || empty($location['longitude'])) return null;

        $lat1 = $target[0]; $lng1 = $target[1];
        $lat2 = (float) $location['latitude']; $lng2 = (float) $location['longitude'];
        $earthRadiusKm = 6371.0;
        $dLat = deg2rad($lat2 - $lat1); $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        return round($earthRadiusKm * (2 * atan2(sqrt($a), sqrt(1 - $a))), 3);
    }

    private function freshPingScore(Executor $executor): int
    {
        $lastSeenRaw = Redis::get("executor:{$executor->id}:last_seen_at");
        if (! $lastSeenRaw) return 40;
        $lastSeen = is_numeric($lastSeenRaw) ? now()->createFromTimestamp((int) $lastSeenRaw) : now()->parse((string) $lastSeenRaw);
        $minutes = $lastSeen->diffInMinutes(now());
        return match (true) { $minutes <= 2 => 100, $minutes <= 5 => 80, $minutes <= 10 => 55, default => 25 };
    }
}
