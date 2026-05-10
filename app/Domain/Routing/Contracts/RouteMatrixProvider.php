<?php

namespace App\Domain\Routing\Contracts;

use App\Domain\Routing\DTO\RouteEtaResult;
use App\Domain\Routing\DTO\RouteLocation;

interface RouteMatrixProvider
{
    public function estimateEta(RouteLocation $from, RouteLocation $to, array $context = []): RouteEtaResult;

    /**
     * @param RouteLocation[] $sources
     * @param RouteLocation[] $destinations
     * @return array<int, array<int, RouteEtaResult>>
     */
    public function matrix(array $sources, array $destinations, array $context = []): array;
}

