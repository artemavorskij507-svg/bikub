<?php

namespace App\Services\Routing;

class Point
{
    public function __construct(
        public float $lat,
        public float $lng,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            (float) ($data['lat'] ?? $data[0]),
            (float) ($data['lng'] ?? $data[1])
        );
    }

    public function toArray(): array
    {
        return [
            'lat' => $this->lat,
            'lng' => $this->lng,
        ];
    }
}
