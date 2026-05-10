<?php

namespace App\Jobs;

use App\Domain\Dispatch\Actions\BuildDomainAwareDispatchScoreAction;
use App\Domain\Dispatch\Actions\CheckCapacityFitAction;
use App\Domain\Dispatch\Actions\CheckExecutorShiftEligibilityAction;
use App\Domain\Dispatch\Actions\CheckTimeWindowFitAction;
use App\Domain\Dispatch\Actions\ResolveRuntimeDispatchRuleSetAction;
use App\Domain\Dispatch\Models\Assignment;
use App\Domain\Exceptions\Actions\OpenOperationExceptionAction;
use App\Domain\Moving\Actions\BuildMovingTeamCandidatesAction;
use App\Domain\Moving\Actions\CreateTeamAssignmentAction;
use App\Domain\Operations\Actions\UpdateServiceJobStatusAction;
use App\Domain\Operations\Actions\WriteJobTimelineAction;
use App\Domain\Operations\Models\ServiceJob;
use App\Models\Operations\Executor;
use App\Domain\Roadside\Actions\ApplyEmergencyAcceptanceTimeoutAction;
use App\Domain\Roadside\Actions\FindNearestCapableEmergencyExecutorAction;
use App\Domain\Roadside\Actions\FindPreemptibleAssignmentsAction;
use App\Domain\Roadside\Actions\PreemptLowPriorityAssignmentAction;
use App\Domain\Routing\Actions\BuildRoutingAwareCandidateDiagnosticsAction;
use App\Domain\Routing\Actions\CompareEtaStrategiesAction;
use App\Domain\Routing\Actions\EstimateRoutingEtaAction;
use App\Domain\Routing\Actions\RecommendReplanAction;
use App\Domain\Routing\Actions\StoreRoutingEtaSnapshotAction;
use App\Models\Operations\DispatchCandidate;
use App\Models\Operations\DispatchRun;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Redis;

class CalculateDispatchCandidatesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $serviceJobId) {}

    public function handle(
        UpdateServiceJobStatusAction $updateServiceJobStatusAction,
        WriteJobTimelineAction $writeJobTimelineAction,
        OpenOperationExceptionAction $openOperationExceptionAction,
        CheckExecutorShiftEligibilityAction $checkExecutorShiftEligibilityAction,
        CheckTimeWindowFitAction $checkTimeWindowFitAction,
        CheckCapacityFitAction $checkCapacityFitAction,
        ResolveRuntimeDispatchRuleSetAction $resolveRuntimeDispatchRuleSetAction,
        BuildDomainAwareDispatchScoreAction $buildDomainAwareDispatchScoreAction,
        FindNearestCapableEmergencyExecutorAction $findNearestCapableEmergencyExecutorAction,
        FindPreemptibleAssignmentsAction $findPreemptibleAssignmentsAction,
        PreemptLowPriorityAssignmentAction $preemptLowPriorityAssignmentAction,
        ApplyEmergencyAcceptanceTimeoutAction $applyEmergencyAcceptanceTimeoutAction,
        BuildMovingTeamCandidatesAction $buildMovingTeamCandidatesAction,
        CreateTeamAssignmentAction $createTeamAssignmentAction,
        EstimateRoutingEtaAction $estimateRoutingEtaAction,
        CompareEtaStrategiesAction $compareEtaStrategiesAction,
        BuildRoutingAwareCandidateDiagnosticsAction $buildRoutingAwareCandidateDiagnosticsAction,
        StoreRoutingEtaSnapshotAction $storeRoutingEtaSnapshotAction,
        RecommendReplanAction $recommendReplanAction,
    ): void
    {
        $job = ServiceJob::query()->findOrFail($this->serviceJobId);
        $dispatchRun = DispatchRun::query()->create([
            'organization_id' => $job->organization_id,
            'service_job_id' => $job->id,
            'mode' => 'auto_assign',
            'status' => 'running',
            'started_at' => now(),
        ]);

        $query = Executor::query()
            ->where('organization_id', $job->organization_id)
            ->where('is_dispatchable', true)
            ->whereIn('status', ['available', 'busy']);

        $targetZoneId = $job->geo_zone_id ?: $job->zone_id;
        if ($targetZoneId) {
            $query->where(function ($q) use ($job) {
                $zoneId = $job->geo_zone_id ?: $job->zone_id;
                $q->where('current_zone_id', $zoneId)
                    ->orWhere('home_zone_id', $zoneId);
            });
        }

        $executors = $query->limit(50)->get();
        $best = null;
        $bestScore = -1.0;
        $runtimeRules = $resolveRuntimeDispatchRuleSetAction->execute($job);
        $candidateDiagnostics = [];

        foreach ($executors as $executor) {
            $distanceKm = $this->distanceKmFromRedis($job, $executor);
            $etaSeconds = $this->estimateEtaSeconds($distanceKm);
            $distanceMeters = is_numeric($distanceKm) ? (int) round(((float) $distanceKm) * 1000) : null;

            $shift = $checkExecutorShiftEligibilityAction->execute($job, $executor, $etaSeconds);
            $timeWindow = $checkTimeWindowFitAction->execute($job, $etaSeconds);
            $capacity = $checkCapacityFitAction->execute($job, $executor);

            $eligible = $shift['eligible'] && $timeWindow['fits'] && $capacity['fits'];
            $reasons = array_values(array_filter([
                $shift['eligible'] ? null : ($shift['reason'] ?? 'shift_ineligible'),
                $timeWindow['fits'] ? null : 'time_window_miss',
                $capacity['fits'] ? null : ($capacity['reason'] ?? 'capacity_mismatch'),
            ]));

            $scored = $buildDomainAwareDispatchScoreAction->execute(
                job: $job,
                executor: $executor,
                etaSeconds: $etaSeconds,
                checks: [
                    'shift' => $shift,
                    'time_window' => $timeWindow,
                    'capacity' => $capacity,
                ],
            );

            $routingResult = $estimateRoutingEtaAction->execute($job, $executor);
            $etaCompare = $compareEtaStrategiesAction->execute($etaSeconds, $distanceMeters, $routingResult);
            $routingDiagnostics = $buildRoutingAwareCandidateDiagnosticsAction->execute($etaCompare);

            $candidate = DispatchCandidate::query()->create([
                'dispatch_run_id' => $dispatchRun->id,
                'service_job_id' => $job->id,
                'executor_id' => $executor->id,
                'eligible' => $eligible,
                'score' => $scored['score'],
                'score_breakdown' => array_merge($scored['breakdown'], [
                    'shift' => $shift,
                    'time_window' => $timeWindow,
                    'capacity' => $capacity,
                    'routing' => $routingDiagnostics,
                    'rejection_reason_primary' => $reasons[0] ?? null,
                ]),
                'ineligibility_reasons' => $reasons,
            ]);

            $storeRoutingEtaSnapshotAction->execute(
                job: $job,
                executor: $executor,
                etaCompare: $routingDiagnostics,
                dispatchRun: $dispatchRun,
                dispatchCandidate: $candidate,
                context: [
                    'service_domain' => $job->service_domain,
                    'job_kind' => $job->job_kind,
                    'time_window_risk' => data_get($timeWindow, 'risk'),
                    'time_window_fits' => (bool) data_get($timeWindow, 'fits', false),
                ],
            );

            $candidateDiagnostics[] = [
                'executor_id' => $executor->id,
                'eligible' => $eligible,
                'score' => $scored['score'],
                'rejection_reason' => $reasons[0] ?? null,
                'reasons' => $reasons,
                'checks' => [
                    'shift_fit' => $shift,
                    'time_window_fit' => $timeWindow,
                    'capacity_fit' => $capacity,
                ],
                'base' => [
                    'distance_km' => $distanceKm,
                    'eta_seconds' => $etaSeconds,
                    'stale_gps' => $this->executorIsStale($executor->id),
                ],
                'modifiers' => data_get($scored, 'breakdown.modifiers', []),
                'routing' => $routingDiagnostics,
                'selected' => false,
            ];

            if ($eligible && $scored['score'] > $bestScore) {
                $best = $executor;
                $bestScore = $scored['score'];
            }
        }

        if ($job->service_domain === 'roadside' && in_array((string) $job->priority, ['urgent', 'emergency'], true)) {
            $nearest = $findNearestCapableEmergencyExecutorAction->execute($job);
            if ($nearest) {
                $nearestExecutor = $executors->firstWhere('id', $nearest['executor_id']);
                if ($nearestExecutor) {
                    $best = $nearestExecutor;
                    $bestScore = max($bestScore, 100.0);
                }
            }

            if ($best) {
                $preemptible = $findPreemptibleAssignmentsAction->execute($job, $best->id, 1);
                foreach ($preemptible as $assignmentToPreempt) {
                    $preemptLowPriorityAssignmentAction->execute(
                        assignment: $assignmentToPreempt,
                        emergencyJobId: $job->id,
                    );
                }
            }
        }

        if (! $best) {
            $updated = $updateServiceJobStatusAction->execute(
                job: $job,
                newStatus: 'failed',
                reason: 'no_executor_found',
                context: ['candidates_count' => $executors->count()],
                actorType: 'system',
            );
            $writeJobTimelineAction->execute(
                job: $updated,
                eventType: 'dispatch_no_executor_found',
                payload: ['candidates_count' => $executors->count()],
            );
            $openOperationExceptionAction->execute(
                job: $updated,
                type: 'no_executor_found',
                severity: 'high',
                detectedBy: 'system',
                payload: ['candidates_count' => $executors->count()],
            );

            $dispatchRun->update([
                'status' => 'no_candidate',
                'summary' => [
                    'total_candidates' => $executors->count(),
                    'eligible_candidates' => $dispatchRun->candidates()->where('eligible', true)->count(),
                    'selected_executor_id' => null,
                    'runtime_rule_set' => data_get($runtimeRules, 'rule_set'),
                    'candidate_diagnostics' => $candidateDiagnostics,
                ],
                'completed_at' => now(),
            ]);

            return;
        }

        $candidateDiagnostics = array_map(function (array $candidate) use ($best): array {
            if ((int) data_get($candidate, 'executor_id', 0) === (int) $best->id) {
                $candidate['selected'] = true;
            }

            return $candidate;
        }, $candidateDiagnostics);

        $replanRecommendations = $recommendReplanAction->execute($job, $candidateDiagnostics);

        $assignment = Assignment::create([
            'organization_id' => $job->organization_id,
            'tenant_id' => $job->tenant_id,
            'service_job_id' => $job->id,
            'executor_id' => $best->id,
            'status' => 'proposed',
            'assignment_mode' => 'auto',
            'score' => 100,
            'score_breakdown' => [
                'domain_aware' => true,
                'runtime_rule_set' => data_get($runtimeRules, 'rule_set'),
            ],
        ]);

        if ($job->service_domain === 'roadside') {
            $assignment = $applyEmergencyAcceptanceTimeoutAction->execute($job, $assignment, $runtimeRules);
        }

        if ($job->service_domain === 'moving') {
            $requiredTeamSize = max(2, (int) ($job->required_team_size ?: data_get($runtimeRules, 'moving.default_required_team_size', 2)));
            $team = $buildMovingTeamCandidatesAction->execute(
                job: $job,
                teamSize: $requiredTeamSize,
                etaResolver: fn (Executor $executor) => $this->estimateEtaSeconds($this->distanceKmFromRedis($job, $executor)),
            );
            if ($team['fits']) {
                $createTeamAssignmentAction->execute(
                    job: $job,
                    memberExecutorIds: $team['member_executor_ids'],
                    teamLeadExecutorId: $team['selected_team_lead_executor_id'] ?: $best->id,
                    teamEtaSeconds: $team['team_eta_seconds'] ?? null,
                    memberEtas: $team['member_etas'] ?? [],
                );
            }
        }

        $job->update([
            'assignment_id' => $assignment->id,
            'executor_id' => $best->id,
        ]);

        $updated = $updateServiceJobStatusAction->execute(
            job: $job->fresh(),
            newStatus: 'assigned',
            reason: 'assignment_proposed',
            context: [
                'assignment_id' => $assignment->id,
                'executor_id' => $best->id,
            ],
            actorType: 'system',
        );
        $writeJobTimelineAction->execute($updated, 'assignment_created', payload: [
            'assignment_id' => $assignment->id,
            'executor_id' => $best->id,
            'status' => $assignment->status,
        ], assignmentId: $assignment->id);
        $writeJobTimelineAction->execute($updated, 'assignment_proposed', payload: [
            'assignment_id' => $assignment->id,
            'executor_id' => $best->id,
        ], assignmentId: $assignment->id);

        $dispatchRun->update([
            'status' => 'completed',
            'summary' => [
                'total_candidates' => $executors->count(),
                'eligible_candidates' => $dispatchRun->candidates()->where('eligible', true)->count(),
                'selected_executor_id' => $best->id,
                'best_score' => $bestScore,
                'runtime_rule_set' => data_get($runtimeRules, 'rule_set'),
                'candidate_diagnostics' => $candidateDiagnostics,
                'routing_shadow_mode' => (bool) config('routing.shadow_mode', true),
                'replan_recommendation_ids' => collect($replanRecommendations)->pluck('id')->filter()->values()->all(),
            ],
            'completed_at' => now(),
        ]);
    }

    private function estimateEtaSeconds(?float $distanceKm): int
    {
        if ($distanceKm === null) {
            return 20 * 60;
        }

        $avgSpeedKmh = 35.0;
        return (int) max(180, round(($distanceKm / $avgSpeedKmh) * 3600));
    }

    private function distanceKmFromRedis(ServiceJob $job, Executor $executor): ?float
    {
        $targetLat = $job->service_lat ?: $job->pickup_lat;
        $targetLng = $job->service_lng ?: $job->pickup_lng;
        if (! $targetLat || ! $targetLng) {
            return null;
        }

        $rawLocation = Redis::get("executor:{$executor->id}:last_location");
        $location = $rawLocation ? json_decode($rawLocation, true) : null;
        if (! $location || empty($location['latitude']) || empty($location['longitude'])) {
            return null;
        }

        $lat1 = (float) $targetLat;
        $lng1 = (float) $targetLng;
        $lat2 = (float) $location['latitude'];
        $lng2 = (float) $location['longitude'];

        $earthRadiusKm = 6371.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return round($earthRadiusKm * (2 * atan2(sqrt($a), sqrt(1 - $a))), 3);
    }

    private function executorIsStale(int $executorId): bool
    {
        $raw = Redis::get("executor:{$executorId}:last_seen_at");
        if (! $raw) {
            return true;
        }

        try {
            $lastSeen = is_numeric($raw)
                ? now()->createFromTimestamp((int) $raw)
                : now()->parse((string) $raw);
        } catch (\Throwable) {
            return true;
        }

        return $lastSeen->lt(now()->subMinutes(10));
    }
}
