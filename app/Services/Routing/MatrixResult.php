<?php

namespace App\Services\Routing;

class MatrixResult
{
    public function __construct(
        public array $distances,
        public array $durations,
        public ?string $provider = null,
    ) {}

    public function toArray(): array
    {
        return [
            'distances' => $this->distances,
            'durations' => $this->durations,
            'provider' => $this->provider,
        ];
    }
}
