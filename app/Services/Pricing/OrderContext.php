<?php

namespace App\Services\Pricing;

use Carbon\CarbonInterface;

class OrderContext
{
    public function __construct(
        public readonly string $serviceType,
        public readonly ?string $category = null,
        public readonly ?string $zone = null,
        public readonly ?float $distanceKm = null,
        public readonly ?float $fromLat = null,
        public readonly ?float $fromLng = null,
        public readonly ?float $toLat = null,
        public readonly ?float $toLng = null,
        public readonly ?float $totalWeightKg = null,
        public readonly ?float $totalVolumeM3 = null,
        public readonly ?CarbonInterface $scheduledAt = null,
        public readonly array $items = [],
        public readonly ?int $userId = null,
        public readonly ?string $ipAddress = null,
        public readonly bool $isUrgent = false,
        public readonly array $metadata = [],
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            serviceType: $data['service_type'],
            category: $data['category'] ?? null,
            zone: $data['zone'] ?? null,
            distanceKm: isset($data['distance_km']) ? (float) $data['distance_km'] : null,
            fromLat: isset($data['from_lat']) ? (float) $data['from_lat'] : null,
            fromLng: isset($data['from_lng']) ? (float) $data['from_lng'] : null,
            toLat: isset($data['to_lat']) ? (float) $data['to_lat'] : null,
            toLng: isset($data['to_lng']) ? (float) $data['to_lng'] : null,
            totalWeightKg: isset($data['total_weight_kg']) ? (float) $data['total_weight_kg'] : null,
            totalVolumeM3: isset($data['total_volume_m3']) ? (float) $data['total_volume_m3'] : null,
            scheduledAt: isset($data['scheduled_at']) && $data['scheduled_at']
                ? (is_string($data['scheduled_at'])
                    ? \Carbon\Carbon::parse($data['scheduled_at'])
                    : ($data['scheduled_at'] instanceof \Carbon\Carbon
                        ? $data['scheduled_at']
                        : null))
                : null,
            items: $data['items'] ?? [],
            userId: $data['user_id'] ?? null,
            ipAddress: $data['ip_address'] ?? null,
            isUrgent: (bool) ($data['is_urgent'] ?? false),
            metadata: $data['metadata'] ?? [],
        );
    }

    public function totalWeightKg(): float
    {
        if ($this->totalWeightKg !== null) {
            return $this->totalWeightKg;
        }

        $sum = 0.0;
        foreach ($this->items as $item) {
            $sum += (float) ($item['weight_kg'] ?? 0);
        }

        return $sum;
    }

    public function totalVolumeM3(): float
    {
        if ($this->totalVolumeM3 !== null) {
            return $this->totalVolumeM3;
        }

        $sum = 0.0;
        foreach ($this->items as $item) {
            $sum += (float) ($item['volume_m3'] ?? 0);
        }

        return $sum;
    }
}
