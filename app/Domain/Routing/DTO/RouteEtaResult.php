<?php

namespace App\Domain\Routing\DTO;

class RouteEtaResult
{
    public function __construct(
        public int $etaSeconds,
        public int $distanceMeters,
        public string $provider,
        public bool $success = true,
        public ?string $error = null,
        public array $raw = [],
    ) {}
}

