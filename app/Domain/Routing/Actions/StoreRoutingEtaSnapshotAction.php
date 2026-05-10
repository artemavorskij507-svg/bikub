<?php

namespace App\Domain\Routing\Actions;

use App\Domain\Operations\Models\ServiceJob;
use App\Domain\Routing\Models\RoutingEtaSnapshot;
use App\Models\Operations\Executor;
use App\Models\Operations\DispatchCandidate;
use App\Models\Operations\DispatchRun;

class StoreRoutingEtaSnapshotAction
{
    public function execute(
        ServiceJob $job,
        Executor $executor,
        array $etaCompare,
        ?DispatchRun $dispatchRun = null,
        ?DispatchCandidate $dispatchCandidate = null,
        array $context = [],
    ): ?RoutingEtaSnapshot {
        if (! (bool) config('routing.shadow_mode', true)) {
            return null;
        }

        if (! (bool) config('routing.store_snapshots', true)) {
            return null;
        }

        if (! data_get($etaCompare, 'routing_available', false)) {
            return null;
        }

        return RoutingEtaSnapshot::query()->create([
            'organization_id' => (string) $job->organization_id,
            'tenant_id' => $job->tenant_id ? (string) $job->tenant_id : null,
            'service_job_id' => $job->id,
            'executor_id' => $executor->id,
            'dispatch_run_id' => $dispatchRun?->id,
            'dispatch_candidate_id' => $dispatchCandidate?->id,
            'heuristic_provider' => 'internal',
            'heuristic_eta_seconds' => data_get($etaCompare, 'heuristic_eta_seconds'),
            'heuristic_distance_meters' => data_get($etaCompare, 'heuristic_distance_meters'),
            'routing_provider' => data_get($etaCompare, 'provider'),
            'routing_eta_seconds' => data_get($etaCompare, 'routing_eta_seconds'),
            'routing_distance_meters' => data_get($etaCompare, 'routing_distance_meters'),
            'eta_delta_seconds' => data_get($etaCompare, 'eta_delta_seconds'),
            'distance_delta_meters' => data_get($etaCompare, 'distance_delta_meters'),
            'would_change_ranking' => (bool) data_get($etaCompare, 'would_change_ranking', false),
            'context' => array_merge($context, [
                'delta_percent' => data_get($etaCompare, 'delta_percent'),
                'significance' => data_get($etaCompare, 'significance'),
                'routing_available' => (bool) data_get($etaCompare, 'routing_available', false),
                'routing_error' => data_get($etaCompare, 'routing_error'),
            ]),
        ]);
    }
}
