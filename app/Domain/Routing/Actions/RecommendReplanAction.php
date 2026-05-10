<?php

namespace App\Domain\Routing\Actions;

use App\Domain\Operations\Models\ServiceJob;
use App\Domain\Routing\Models\ReplanRecommendation;

class RecommendReplanAction
{
    public function execute(ServiceJob $job, array $candidateDiagnostics): array
    {
        if (! (bool) config('routing.shadow_mode', true)) {
            return [];
        }

        $created = [];
        $selected = collect($candidateDiagnostics)->first(fn (array $candidate): bool => (bool) data_get($candidate, 'selected', false));
        $eligible = collect($candidateDiagnostics)
            ->filter(fn (array $candidate): bool => (bool) data_get($candidate, 'eligible', false))
            ->values();

        $bestRouting = $eligible
            ->filter(fn (array $candidate): bool => data_get($candidate, 'routing.routing_available', false))
            ->sortBy(fn (array $candidate) => (int) data_get($candidate, 'routing.routing_eta_seconds', PHP_INT_MAX))
            ->first();

        if ($selected && $bestRouting) {
            $selectedExecutorId = (int) data_get($selected, 'executor_id', 0);
            $recommendedExecutorId = (int) data_get($bestRouting, 'executor_id', 0);
            $selectedRoutingEta = (int) data_get($selected, 'routing.routing_eta_seconds', PHP_INT_MAX);
            $bestRoutingEta = (int) data_get($bestRouting, 'routing.routing_eta_seconds', PHP_INT_MAX);
            $improvement = $selectedRoutingEta - $bestRoutingEta;

            if ($recommendedExecutorId > 0 && $recommendedExecutorId !== $selectedExecutorId && $improvement >= 120 && ! in_array((string) $job->status, ['arrived', 'in_progress'], true)) {
                $created[] = $this->upsertOpen(
                    job: $job,
                    type: 'better_executor_available',
                    severity: $improvement >= 600 ? 'high' : 'medium',
                    currentExecutorId: $selectedExecutorId ?: null,
                    recommendedExecutorId: $recommendedExecutorId,
                    payload: [
                        'routing_eta_improvement_seconds' => $improvement,
                        'selected_executor' => $selectedExecutorId,
                        'recommended_executor' => $recommendedExecutorId,
                    ],
                );
            }
        }

        if ($selected) {
            $deltaPercent = (float) data_get($selected, 'routing.delta_percent', 0.0);
            $significance = (string) data_get($selected, 'routing.significance', 'low');

            if (abs($deltaPercent) > 20) {
                $created[] = $this->upsertOpen(
                    job: $job,
                    type: 'eta_drift',
                    severity: $significance === 'high' ? 'high' : 'medium',
                    currentExecutorId: (int) data_get($selected, 'executor_id', 0) ?: null,
                    recommendedExecutorId: null,
                    payload: [
                        'delta_percent' => $deltaPercent,
                        'eta_delta_seconds' => data_get($selected, 'routing.eta_delta_seconds'),
                    ],
                );
            }

            $windowFits = data_get($selected, 'checks.time_window_fit.fits');
            $windowRisk = (string) data_get($selected, 'checks.time_window_fit.risk', '');
            if ($windowFits === false || $windowRisk === 'high') {
                $created[] = $this->upsertOpen(
                    job: $job,
                    type: 'window_risk',
                    severity: 'high',
                    currentExecutorId: (int) data_get($selected, 'executor_id', 0) ?: null,
                    recommendedExecutorId: null,
                    payload: [
                        'window_fit' => data_get($selected, 'checks.time_window_fit'),
                        'routing' => data_get($selected, 'routing'),
                    ],
                );
            }

            $routingAvailable = (bool) data_get($selected, 'routing.routing_available', false);
            $staleSignal = (bool) data_get($selected, 'base.stale_gps', false);
            if (! $routingAvailable && $staleSignal) {
                $created[] = $this->upsertOpen(
                    job: $job,
                    type: 'stale_route',
                    severity: 'medium',
                    currentExecutorId: (int) data_get($selected, 'executor_id', 0) ?: null,
                    recommendedExecutorId: null,
                    payload: [
                        'routing_error' => data_get($selected, 'routing.routing_error'),
                    ],
                );
            }
        }

        return array_values(array_filter($created));
    }

    private function upsertOpen(
        ServiceJob $job,
        string $type,
        string $severity,
        ?int $currentExecutorId,
        ?int $recommendedExecutorId,
        array $payload,
    ): ?ReplanRecommendation {
        $existing = ReplanRecommendation::query()
            ->where('organization_id', (string) $job->organization_id)
            ->where('service_job_id', $job->id)
            ->where('type', $type)
            ->whereIn('status', ['open', 'acknowledged'])
            ->latest('id')
            ->first();

        if ($existing) {
            $existing->update([
                'severity' => $severity,
                'current_executor_id' => $currentExecutorId,
                'recommended_executor_id' => $recommendedExecutorId,
                'payload' => $payload,
                'detected_at' => now(),
                'status' => 'open',
            ]);

            return $existing->fresh();
        }

        return ReplanRecommendation::query()->create([
            'organization_id' => (string) $job->organization_id,
            'tenant_id' => $job->tenant_id ? (string) $job->tenant_id : null,
            'service_job_id' => $job->id,
            'current_executor_id' => $currentExecutorId,
            'recommended_executor_id' => $recommendedExecutorId,
            'type' => $type,
            'severity' => $severity,
            'status' => 'open',
            'payload' => $payload,
            'detected_at' => now(),
        ]);
    }
}

