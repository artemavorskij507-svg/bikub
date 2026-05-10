<?php

namespace App\Services\Routing;

class RouteResult
{
    public function __construct(
        public float $distanceKm,
        public int $durationMin,
        public ?string $geometry = null,
        public array $steps = [],
        public ?array $tolls = null,
        public ?string $provider = null,
    ) {}

    public function toArray(): array
    {
        return [
            'distance_km' => round($this->distanceKm, 2),
            'duration_min' => $this->durationMin,
            'geometry' => $this->geometry,
            'steps' => $this->steps,
            'tolls' => $this->tolls,
            'provider' => $this->provider,
        ];
    }
}
