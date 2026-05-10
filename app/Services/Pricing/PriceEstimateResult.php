<?php

namespace App\Services\Pricing;

class PriceEstimateResult
{
    /**
     * @param  array<int, array{rule_id:int|null, rule_name:string, type:string, amount:float}>  $breakdown
     */
    public function __construct(
        public readonly float $subtotal,
        public readonly float $total,
        public readonly string $currency,
        public readonly array $breakdown = [],
        public readonly int $durationMs = 0,
    ) {}
}
