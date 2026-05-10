<?php

namespace App\Services\Errand;

use App\Models\ErrandOrderDetails;
use InvalidArgumentException;

class ErrandPricingService
{
    /**
     * Рассчитать цену поручения на основе деталей и дистанции.
     *
     * @param  float  $distanceKm  Общая длина маршрута в км (по результатам расчёта маршрута)
     * @return array{
     *     base_fee: int,
     *     distance_fee: int,
     *     time_fee: int,
     *     complexity_fee: int,
     *     trusted_helper_fee: int,
     *     urgency_fee: int,
     *     material_advance_amount: int,
     *     total_estimated_price: int
     * }
     */
    public function estimate(ErrandOrderDetails $details, float $distanceKm): array
    {
        if ($distanceKm < 0) {
            throw new InvalidArgumentException('Distance cannot be negative.');
        }

        $config = config('errand.pricing');

        $baseFee = (int) ($config['base_fee'] ?? 0);

        $distanceFeePerKm = (int) ($config['distance_fee_per_km'] ?? 0);
        $distanceFee = (int) round($distanceFeePerKm * max($distanceKm, 0));

        $timeFeePerMinute = (int) ($config['time_fee_per_minute'] ?? 0);
        $expectedMinutes = (int) ($details->expected_duration_minutes ?? 0);
        $timeFee = (int) max($expectedMinutes, 0) * $timeFeePerMinute;

        $complexityLevel = (int) ($details->complexity_level ?? 1);
        $complexityMultipliers = $config['complexity_multipliers'] ?? [];
        $complexityMultiplier = (float) ($complexityMultipliers[$complexityLevel] ?? 1.0);

        // Базовая сумма до учёта сложности и срочности
        $subtotal = $baseFee + $distanceFee + $timeFee;

        $complexityFee = (int) round($subtotal * ($complexityMultiplier - 1.0));
        if ($complexityFee < 0) {
            $complexityFee = 0;
        }

        // Trusted Helper
        $trustedHelperFee = 0;
        if ($details->requires_trusted_helper) {
            $trustedHelperFee = (int) ($config['trusted_helper_fee'] ?? 0);
        }

        // Срочность: множитель к (base + distance + time + complexity)
        $urgencyFee = 0;
        $urgencyMultiplier = (float) ($config['urgency_multiplier'] ?? 1.0);
        if ($details->is_urgent && $urgencyMultiplier > 1.0) {
            $urgencyBase = $subtotal + $complexityFee;
            $urgencyFee = (int) round($urgencyBase * ($urgencyMultiplier - 1.0));
        }

        // Аванс на материалы (покупки) уже передаётся в details
        $materialAdvance = (int) ($details->material_advance_amount ?? 0);

        $total = $baseFee
            + $distanceFee
            + $timeFee
            + $complexityFee
            + $trustedHelperFee
            + $urgencyFee
            + $materialAdvance;

        $minTotal = (int) ($config['min_total_estimated_price'] ?? 0);
        $maxTotal = (int) ($config['max_total_estimated_price'] ?? 0);

        if ($minTotal > 0 && $total < $minTotal) {
            $total = $minTotal;
        }

        if ($maxTotal > 0 && $total > $maxTotal) {
            $total = $maxTotal;
        }

        return [
            'base_fee' => $baseFee,
            'distance_fee' => $distanceFee,
            'time_fee' => $timeFee,
            'complexity_fee' => $complexityFee,
            'trusted_helper_fee' => $trustedHelperFee,
            'urgency_fee' => $urgencyFee,
            'material_advance_amount' => $materialAdvance,
            'total_estimated_price' => $total,
        ];
    }

    /**
     * Удобный метод: посчитать и сохранить поля в ErrandOrderDetails.
     */
    public function estimateAndFill(ErrandOrderDetails $details, float $distanceKm): ErrandOrderDetails
    {
        $result = $this->estimate($details, $distanceKm);

        $details->base_fee = $result['base_fee'];
        $details->distance_fee = $result['distance_fee'];
        $details->time_fee = $result['time_fee'];
        $details->complexity_fee = $result['complexity_fee'];
        $details->trusted_helper_fee = $result['trusted_helper_fee'];
        $details->urgency_fee = $result['urgency_fee'] ?? null;
        $details->material_advance_amount = $result['material_advance_amount'];
        $details->total_estimated_price = $result['total_estimated_price'];

        return $details;
    }
}
