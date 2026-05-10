<?php

namespace App\Domain\Routing\Actions;

use App\Domain\Routing\Queries\RoutingShadowMetricsQuery;

class BuildRoutingBaselineReportAction
{
    public function __construct(
        private readonly RoutingShadowMetricsQuery $routingShadowMetricsQuery,
        private readonly CheckRoutingProviderHealthAction $checkRoutingProviderHealthAction,
    ) {}

    public function execute(?string $organizationId = null, int $days = 3): array
    {
        return [
            'generated_at' => now()->toIso8601String(),
            'provider_health' => $this->checkRoutingProviderHealthAction->execute(),
            'metrics' => $this->routingShadowMetricsQuery->execute($organizationId, $days),
        ];
    }
}

