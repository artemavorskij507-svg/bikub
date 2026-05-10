<?php

namespace App\Domain\Ops\Queries;

use App\Domain\Dispatch\Actions\ResolveRuntimeDispatchRuleSetAction;
use App\Domain\Moving\Models\TeamAssignment;
use App\Domain\Operations\Models\ServiceJob;
use App\Models\Operations\Assignment;
use App\Models\Operations\DispatchRun;
use App\Support\Ops\CandidateDiagnosticsPresenter;
use App\Support\Ops\DispatchReasonPresenter;
use App\Support\Ops\JobStatusPresenter;
use App\Support\Ops\SlaLabelPresenter;

class LiveJobDrawerQuery
{
    public function __construct(
        private readonly DispatchCandidatesQuery $dispatchCandidatesQuery,
        private readonly ResolveRuntimeDispatchRuleSetAction $resolveRuntimeDispatchRuleSetAction,
        private readonly ReplanRecommendationsQuery $replanRecommendationsQuery,
    ) {}

    public function execute(string $organizationId, int $jobId): array
    {
        $job = ServiceJob::query()
            ->where('organization_id', (string) $organizationId)
            ->with([
                'executor:id,name,display_name,status,vehicle_type',
                'currentAssignment:id,service_job_id,executor_id,status,eta_at,accepted_at,arrived_at,started_at',
                'currentAssignment.executor:id,name,display_name,status,vehicle_type',
                'slaTimers:id,service_job_id,status,metric_name,dispatch_state,arrival_state,completion_state,warning_at,breach_at,last_evaluated_at',
                'exceptions' => function ($q): void {
                    $q->whereIn('status', ['open', 'acknowledged', 'investigating', 'mitigated'])
                        ->latest('detected_at')
                        ->limit(20);
                },
                'timelines' => function ($q): void {
                    $q->latest('occurred_at')->limit(20);
                },
            ])
            ->findOrFail($jobId);

        $executor = $job->currentAssignment?->executor ?: $job->executor;
        $slaState = SlaLabelPresenter::stateForJob($job);
        $jobPoint = $job->service_lat && $job->service_lng
            ? ['lat' => (float) $job->service_lat, 'lng' => (float) $job->service_lng]
            : (($job->pickup_lat && $job->pickup_lng) ? ['lat' => (float) $job->pickup_lat, 'lng' => (float) $job->pickup_lng] : null);

        $latestRun = DispatchRun::query()
            ->where('service_job_id', $job->id)
            ->latest('id')
            ->first();
        $runtimeRules = $this->resolveRuntimeDispatchRuleSetAction->execute($job);
        $selectedExecutorId = (int) ($job->currentAssignment?->executor_id ?: $job->executor_id ?: 0);
        $movingTeamAssignment = TeamAssignment::query()
            ->where('service_job_id', $job->id)
            ->latest('id')
            ->first();
        $roadsideEmergency = (string) $job->service_domain === 'roadside'
            && (in_array((string) $job->priority, ['urgent', 'emergency'], true)
                || (bool) data_get($job->metadata, 'is_emergency', false));
        $dispatchCandidates = [];

        if ($latestRun) {
            $dispatchCandidates = $latestRun->candidates()
                ->with('executor:id,name,display_name,status,vehicle_type')
                ->orderByDesc('score')
                ->limit(10)
                ->get()
                ->map(fn ($candidate) => CandidateDiagnosticsPresenter::fromDispatchCandidate(
                    candidate: $candidate,
                    selectedExecutorId: $selectedExecutorId ?: null,
                    runtimeRules: $runtimeRules,
                    serviceDomain: (string) $job->service_domain,
                    movingTeamAssignment: $movingTeamAssignment,
                    roadsideEmergency: $roadsideEmergency,
                ))
                ->values()
                ->all();
        }

        if ($dispatchCandidates === []) {
            $dispatchCandidates = collect($this->dispatchCandidatesQuery->execute(
                organizationId: $organizationId,
                lat: $jobPoint['lat'] ?? null,
                lng: $jobPoint['lng'] ?? null,
                limit: 10,
            ))
                ->map(fn (array $candidate) => CandidateDiagnosticsPresenter::fromFallbackCandidate(
                    candidate: $candidate,
                    selectedExecutorId: $selectedExecutorId ?: null,
                    runtimeRules: $runtimeRules,
                    serviceDomain: (string) $job->service_domain,
                    movingTeamAssignment: $movingTeamAssignment,
                    roadsideEmergency: $roadsideEmergency,
                ))
                ->values()
                ->all();
        }

        $preemptedAssignments = Assignment::query()
            ->where('organization_id', $job->organization_id)
            ->where('cancel_reason', 'roadside_emergency_preemption')
            ->latest('updated_at')
            ->limit(50)
            ->get(['id', 'service_job_id', 'executor_id', 'metadata'])
            ->filter(fn (Assignment $assignment) => (int) data_get($assignment->metadata, 'preempted_by_job_id') === (int) $job->id)
            ->values();

        $roadsideHints = [
            'is_emergency' => $roadsideEmergency,
            'emergency_fast_lane_applied' => $roadsideEmergency,
            'acceptance_timeout_seconds' => (int) ($job->currentAssignment?->acceptance_timeout_seconds
                ?: data_get($runtimeRules, 'roadside.acceptance_timeout_seconds', 0)),
            'acceptance_deadline_at' => optional($job->currentAssignment?->acceptance_deadline_at)->toIso8601String(),
            'preempted_assignments_count' => $preemptedAssignments->count(),
            'preempted_assignments' => $preemptedAssignments->map(fn (Assignment $assignment) => [
                'assignment_id' => $assignment->id,
                'service_job_id' => $assignment->service_job_id,
                'executor_id' => $assignment->executor_id,
            ])->all(),
        ];

        $movingHints = [
            'required_team_size' => (int) ($job->required_team_size ?: data_get($runtimeRules, 'moving.default_required_team_size', 0)),
            'team_candidate_found' => $movingTeamAssignment !== null,
            'team_size_found' => count((array) ($movingTeamAssignment?->member_executor_ids_json ?? [])),
            'team_eta_at' => optional($movingTeamAssignment?->eta_at)->toIso8601String(),
            'team_eta_seconds' => $movingTeamAssignment?->eta_at ? max(0, now()->diffInSeconds($movingTeamAssignment->eta_at, false)) : null,
            'member_executor_ids' => (array) ($movingTeamAssignment?->member_executor_ids_json ?? []),
            'member_etas' => (array) data_get($movingTeamAssignment?->metadata, 'member_etas', []),
            'team_lead_executor_id' => $movingTeamAssignment?->team_lead_executor_id,
        ];

        return [
            'entity_updated_at' => optional($job->updated_at)->toIso8601String(),
            'drawer_version' => optional($job->updated_at)?->format('Y-m-d H:i:s.u'),
            'job' => [
                'id' => $job->id,
                'domain' => $job->service_domain,
                'kind' => $job->job_kind ?: $job->job_type,
                'status' => JobStatusPresenter::normalize($job->status),
                'status_label' => JobStatusPresenter::label($job->status),
                'priority' => (string) $job->priority,
                'customer_id' => $job->customer_id,
                'eta' => optional($job->promised_eta_at ?: $job->currentAssignment?->eta_at)?->toIso8601String(),
                'sla_state' => $slaState,
                'sla_label' => SlaLabelPresenter::label($slaState),
                'exceptions_count' => (int) $job->exceptions->count(),
                'updated_at' => optional($job->updated_at)->toIso8601String(),
            ],
            'executor' => $executor ? [
                'id' => $executor->id,
                'display_name' => $executor->display_name ?: $executor->name,
                'status' => $executor->status,
                'vehicle_type' => $executor->vehicle_type,
            ] : null,
            'assignment' => $job->currentAssignment ? [
                'id' => $job->currentAssignment->id,
                'status' => $job->currentAssignment->status,
                'eta' => optional($job->currentAssignment->eta_at)?->toIso8601String(),
                'accepted_at' => optional($job->currentAssignment->accepted_at)?->toIso8601String(),
            ] : null,
            'sla' => $job->slaTimers->map(fn ($timer) => [
                'id' => $timer->id,
                'metric' => $timer->metric_name,
                'status' => $timer->status,
                'warning_at' => optional($timer->warning_at)?->toIso8601String(),
                'breach_at' => optional($timer->breach_at)?->toIso8601String(),
            ])->values()->all(),
            'exceptions' => $job->exceptions->map(fn ($exception) => [
                'id' => $exception->id,
                'type' => $exception->canonical_type,
                'severity' => $exception->severity,
                'status' => $exception->status,
                'detected_at' => optional($exception->detected_at)?->toIso8601String(),
                'payload' => $exception->payload,
            ])->values()->all(),
            'timeline' => $job->timelines->map(fn ($event) => [
                'id' => $event->id,
                'event_type' => $event->event_type,
                'occurred_at' => optional($event->occurred_at)?->toIso8601String(),
                'actor_type' => $event->actor_type,
                'actor_id' => $event->actor_id,
                'payload' => $event->event_payload,
            ])->values()->all(),
            'dispatch_candidates' => $dispatchCandidates,
            'routing' => [
                'shadow_mode' => (bool) config('routing.shadow_mode', true),
                'provider' => (string) config('routing.default_provider', 'null'),
            ],
            'replan_recommendations' => $this->replanRecommendationsQuery->execute(
                organizationId: (string) $organizationId,
                serviceJobId: (int) $job->id,
                limit: 20,
            ),
            'runtime' => [
                'effective_rule_values' => CandidateDiagnosticsPresenter::effectiveRuntimeRuleValues($runtimeRules),
            ],
            'special_hints' => [
                'roadside' => $roadsideHints,
                'moving' => $movingHints,
            ],
            'diagnostics' => [
                'summary' => [
                    'selected_executor_id' => $selectedExecutorId ?: null,
                    'eligible_candidates_count' => count(array_filter($dispatchCandidates, fn (array $candidate) => (bool) data_get($candidate, 'is_eligible', false))),
                    'ineligible_candidates_count' => count(array_filter($dispatchCandidates, fn (array $candidate) => ! (bool) data_get($candidate, 'is_eligible', false))),
                    'top_rejection_reasons' => collect($dispatchCandidates)
                        ->pluck('rejection_reason')
                        ->filter()
                        ->countBy()
                        ->mapWithKeys(fn ($count, $reason) => [DispatchReasonPresenter::label((string) $reason) => $count])
                        ->all(),
                ],
            ],
        ];
    }
}
