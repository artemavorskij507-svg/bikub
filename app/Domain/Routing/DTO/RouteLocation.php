<?php

namespace App\Domain\Routing\DTO;

class RouteLocation
{
    public function __construct(
        public float $lat,
        public float $lng,
        public ?string $label = null,
        public array $meta = [],
    ) {}
}

