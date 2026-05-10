<?php

namespace App\Services\Pricing\Support;

class PriceMath
{
    public static function round(float $value): float
    {
        return round($value, config('pricing.decimals', 2));
    }
}
