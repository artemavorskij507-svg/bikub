<?php

namespace App\Services\Operations;

use App\Events\Operations\AssignmentCreated;
use App\Events\Operations\DispatchRequested;
use App\Events\Operations\EtaRecalculated;
use App\Events\Operations\JobStatusChanged;
use App\Models\Operations\Assignment;
use App\Models\Operations\DispatchCandidate;
use App\Models\Operations\DispatchRun;
use App\Models\Operations\Executor;
use App\Models\Operations\JobStateTransition;
use App\Models\Operations\ServiceJob;
use Illuminate\Support\Facades\DB;

class DispatchEngineService
{
    public function __construct(
        private readonly DispatchScoringService $scoringService,
        private readonly JobTimelineService $timelineService
    ) {}

    public function requestDispatch(ServiceJob $job, string $mode = 'auto_assign', array $filters = []): array
    {
        event(new DispatchRequested($job, $mode));
        $this->timelineService->log($job, 'dispatch_requested', ['mode' => $mode, 'filters' => $filters]);

        return DB::transaction(function () use ($job, $mode, $filters) {
            $dispatchRun = DispatchRun::create([
                'organization_id' => $job->organization_id,
                'service_job_id' => $job->id,
                'mode' => $mode,
                'status' => 'running',
                'filters' => $filters,
                'started_at' => now(),
            ]);

            $candidateQuery = Executor::query()
                ->with(['skills', 'shifts'])
                ->when($job->organization_id, fn ($q) => $q->where('organization_id', $job->organization_id))
                ->when(isset($filters['executor_type']), fn ($q) => $q->where('executor_type', $filters['executor_type']));

            $candidates = $candidateQuery->get();

            $bestCandidate = null;
            $bestScore = -1;

            foreach ($candidates as $candidate) {
                $evaluation = $this->scoringService->evaluate($job, $candidate);

                DispatchCandidate::create([
                    'dispatch_run_id' => $dispatchRun->id,
                    'service_job_id' => $job->id,
                    'executor_id' => $candidate->id,
                    'eligible' => $evaluation['eligible'],
                    'score' => $evaluation['score'],
                    'score_breakdown' => $evaluation['breakdown'],
                    'ineligibility_reasons' => $evaluation['reasons'],
                ]);

                if ($evaluation['eligible'] && $evaluation['score'] > $bestScore) {
                    $bestCandidate = $candidate;
                    $bestScore = $evaluation['score'];
                }
            }

            $assignment = null;
            if ($bestCandidate && in_array($mode, ['auto_assign', 'dispatcher_approval'], true)) {
                $assignment = Assignment::create([
                    'organization_id' => $job->organization_id,
                    'service_job_id' => $job->id,
                    'executor_id' => $bestCandidate->id,
                    'dispatch_run_id' => $dispatchRun->id,
                    'assignment_mode' => $mode,
                    'status' => $mode === 'dispatcher_approval' ? 'awaiting_approval' : 'assigned',
                    'route_plan' => $this->buildRoutePlan($job, $bestCandidate),
                ]);

                $oldStatus = $job->status;
                $job->update([
                    'status' => $mode === 'dispatcher_approval' ? 'awaiting_dispatch_approval' : 'assigned',
                    'customer_eta_at' => now()->addMinutes(15),
                ]);
                event(new JobStatusChanged($job->fresh(), $oldStatus, $job->status));

                JobStateTransition::create([
                    'service_job_id' => $job->id,
                    'assignment_id' => $assignment->id,
                    'from_status' => $oldStatus,
                    'to_status' => $job->status,
                    'event_type' => 'assignment_created',
                    'payload' => [
                        'dispatch_run_id' => $dispatchRun->id,
                        'executor_id' => $bestCandidate->id,
                        'mode' => $mode,
                    ],
                    'transitioned_at' => now(),
                ]);

                event(new AssignmentCreated($assignment));
                $this->timelineService->log(
                    $job,
                    'assignment_created',
                    ['executor_id' => $bestCandidate->id, 'mode' => $mode, 'score' => $bestScore],
                    $assignment
                );
            }

            $dispatchRun->update([
                'status' => $assignment ? 'completed' : 'no_candidate',
                'summary' => [
                    'total_candidates' => $candidates->count(),
                    'eligible_candidates' => $dispatchRun->candidates()->where('eligible', true)->count(),
                    'selected_executor_id' => $assignment?->executor_id,
                    'best_score' => $bestScore > 0 ? $bestScore : null,
                ],
                'completed_at' => now(),
            ]);

            return [
                'dispatch_run' => $dispatchRun->fresh('candidates'),
                'assignment' => $assignment?->fresh(),
            ];
        });
    }

    public function replan(ServiceJob $job, string $reason, array $context = []): array
    {
        $dispatch = $this->requestDispatch($job, $context['mode'] ?? 'auto_assign', [
            'replan_reason' => $reason,
        ]);

        event(new EtaRecalculated(
            $job->fresh(),
            now()->addMinutes(20),
            ['reason' => $reason]
        ));
        $this->timelineService->log($job, 'dispatch_replanned', ['reason' => $reason, 'context' => $context]);

        return $dispatch;
    }

    private function buildRoutePlan(ServiceJob $job, Executor $executor): array
    {
        return [
            'executor_id' => $executor->id,
            'steps' => array_values(array_filter([
                $job->pickup_point ? ['type' => 'pickup', 'point' => $job->pickup_point] : null,
                $job->service_point ? ['type' => 'service', 'point' => $job->service_point] : null,
                $job->dropoff_point ? ['type' => 'dropoff', 'point' => $job->dropoff_point] : null,
            ])),
            'generated_at' => now()->toIso8601String(),
        ];
    }
}
