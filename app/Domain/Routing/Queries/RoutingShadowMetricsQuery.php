<?php

namespace App\Domain\Routing\Queries;

use App\Domain\Routing\Models\ReplanRecommendation;
use App\Domain\Routing\Models\RoutingEtaSnapshot;
use App\Models\Operations\DispatchCandidate;
use App\Models\Operations\ServiceJob;

class RoutingShadowMetricsQuery
{
    public function execute(?string $organizationId = null, int $days = 3): array
    {
        $days = max(1, min(30, $days));
        $since = now()->subDays($days);

        $snapshotQuery = RoutingEtaSnapshot::query()
            ->where('created_at', '>=', $since);

        if ($organizationId !== null && $organizationId !== '') {
            $snapshotQuery->where('organization_id', (string) $organizationId);
        }

        $snapshots = $snapshotQuery->get([
            'id',
            'service_job_id',
            'heuristic_eta_seconds',
            'routing_eta_seconds',
            'eta_delta_seconds',
            'would_change_ranking',
            'context',
            'created_at',
        ]);

        $totalSnapshots = $snapshots->count();
        $validDelta = $snapshots->filter(
            static fn (RoutingEtaSnapshot $row): bool => (int) ($row->heuristic_eta_seconds ?? 0) > 0
                && $row->eta_delta_seconds !== null
        );
        $avgDeltaSeconds = $validDelta->isEmpty() ? 0.0 : round((float) $validDelta->avg('eta_delta_seconds'), 2);

        $deltaPercents = $validDelta->map(function (RoutingEtaSnapshot $row): float {
            $heuristic = (int) $row->heuristic_eta_seconds;
            if ($heuristic <= 0) {
                return 0.0;
            }

            return round((((int) $row->eta_delta_seconds) / $heuristic) * 100, 2);
        });
        $avgDeltaPercent = $deltaPercents->isEmpty() ? 0.0 : round((float) $deltaPercents->avg(), 2);

        $significance = [
            'low' => 0,
            'medium' => 0,
            'high' => 0,
            'unavailable' => 0,
        ];
        foreach ($deltaPercents as $percent) {
            $abs = abs((float) $percent);
            if ($abs < 5) {
                $significance['low']++;
            } elseif ($abs <= 15) {
                $significance['medium']++;
            } else {
                $significance['high']++;
            }
        }
        $significance['unavailable'] = max(0, $totalSnapshots - (int) $deltaPercents->count());

        $rankingDriftCount = $snapshots->filter(
            static fn (RoutingEtaSnapshot $row): bool => (bool) $row->would_change_ranking
        )->count();

        $recommendationQuery = ReplanRecommendation::query()->where('detected_at', '>=', $since);
        if ($organizationId !== null && $organizationId !== '') {
            $recommendationQuery->where('organization_id', (string) $organizationId);
        }
        $recommendationsByType = $recommendationQuery
            ->selectRaw('type, count(*) as aggregate')
            ->groupBy('type')
            ->pluck('aggregate', 'type')
            ->toArray();

        $jobQuery = ServiceJob::query()->where('created_at', '>=', $since);
        if ($organizationId !== null && $organizationId !== '') {
            $jobQuery->where('organization_id', (string) $organizationId);
        }
        $jobDomainMap = $jobQuery->get(['id', 'service_domain'])->pluck('service_domain', 'id');

        $domainBuckets = [];
        foreach ($snapshots as $snapshot) {
            $domain = (string) ($jobDomainMap[$snapshot->service_job_id] ?? 'unknown');
            if (! isset($domainBuckets[$domain])) {
                $domainBuckets[$domain] = [
                    'service_domain' => $domain,
                    'snapshots' => 0,
                    'avg_eta_delta_seconds' => 0.0,
                    'high_significance_count' => 0,
                    'ranking_drift_count' => 0,
                    'window_risk_count' => 0,
                ];
            }

            $domainBuckets[$domain]['snapshots']++;
            $domainBuckets[$domain]['avg_eta_delta_seconds'] += (float) ($snapshot->eta_delta_seconds ?? 0);
            $domainBuckets[$domain]['ranking_drift_count'] += $snapshot->would_change_ranking ? 1 : 0;

            $context = (array) $snapshot->context;
            $risk = (string) data_get($context, 'time_window_risk', '');
            if ($risk === 'high') {
                $domainBuckets[$domain]['window_risk_count']++;
            }

            $heuristic = (int) ($snapshot->heuristic_eta_seconds ?? 0);
            if ($heuristic > 0 && $snapshot->eta_delta_seconds !== null) {
                $absPercent = abs((((int) $snapshot->eta_delta_seconds) / $heuristic) * 100);
                if ($absPercent > 15) {
                    $domainBuckets[$domain]['high_significance_count']++;
                }
            }
        }

        foreach ($domainBuckets as &$bucket) {
            if ($bucket['snapshots'] > 0) {
                $bucket['avg_eta_delta_seconds'] = round($bucket['avg_eta_delta_seconds'] / $bucket['snapshots'], 2);
            }
        }
        unset($bucket);

        $candidateQuery = DispatchCandidate::query()->where('created_at', '>=', $since);
        if ($organizationId !== null && $organizationId !== '') {
            $candidateQuery->whereIn('service_job_id', $jobDomainMap->keys()->all());
        }
        $candidates = $candidateQuery->get(['score_breakdown']);
        $providerErrorsCount = $candidates->filter(function (DispatchCandidate $candidate): bool {
            $routingAvailable = (bool) data_get($candidate->score_breakdown, 'routing.routing_available', false);
            $routingError = data_get($candidate->score_breakdown, 'routing.routing_error');

            return ! $routingAvailable && is_string($routingError) && $routingError !== '';
        })->count();

        return [
            'period_days' => $days,
            'since' => $since->toIso8601String(),
            'organization_id' => $organizationId !== null && $organizationId !== '' ? (string) $organizationId : null,
            'total_snapshots' => $totalSnapshots,
            'avg_eta_delta_seconds' => $avgDeltaSeconds,
            'avg_delta_percent' => $avgDeltaPercent,
            'significance_distribution' => $significance,
            'ranking_drift_count' => $rankingDriftCount,
            'provider_errors_count' => $providerErrorsCount,
            'recommendations_by_type' => $recommendationsByType,
            'breakdown_by_service_domain' => array_values($domainBuckets),
        ];
    }
}

