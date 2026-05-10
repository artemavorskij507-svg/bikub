<?php

namespace App\Services\EcoDisposal;

use App\Models\DisposalItem;

class EcoDisposalPricingService
{
    /**
     * Estimate eco disposal job.
     *
     * @param  array<int, array{disposal_item_id:int, quantity:int}>  $itemsPayload
     */
    public function estimate(
        array $itemsPayload,
        ?int $floor,
        bool $hasElevator,
        ?int $parkingDistanceMeters,
        bool $expressRequested,
        ?string $zoneCode = null
    ): EcoDisposalEstimateDto {
        $estimatedVolume = 0.0;
        $estimatedWeight = 0.0;
        $basePrice = 0.0;
        $aggregatedDifficulty = 1.0;

        foreach ($itemsPayload as $payloadItem) {
            $item = DisposalItem::find($payloadItem['disposal_item_id'] ?? null);
            $qty = (int) ($payloadItem['quantity'] ?? 1);

            if (! $item || $qty <= 0) {
                continue;
            }

            $volume = (float) ($item->volume_m3 ?? 0);
            $weight = (float) ($item->weight_kg ?? 0);
            $difficulty = (float) ($item->difficulty_coefficient ?? 1.0);
            $price = (float) ($item->base_price_nok ?? 0);

            $estimatedVolume += $volume * $qty;
            $estimatedWeight += $weight * $qty;

            // base price with item-level difficulty
            $basePrice += $price * $qty * $difficulty;

            // aggregate difficulty (simple max for now)
            $aggregatedDifficulty = max($aggregatedDifficulty, $difficulty);
        }

        // difficulty factor from building conditions
        $difficultyFactor = $this->calculateDifficultyFactor($floor, $hasElevator, $parkingDistanceMeters);

        // express surcharge (simple %)
        $expressSurcharge = 0.0;
        $expressFactor = 1.0;
        if ($expressRequested) {
            $expressFactor = 1.25; // +25% for express
        }

        // distance surcharge (e.g. per every 25m over 20m)
        $distanceSurcharge = 0.0;
        if ($parkingDistanceMeters !== null && $parkingDistanceMeters > 20) {
            $distanceBlocks = (int) ceil(($parkingDistanceMeters - 20) / 25);
            $distanceSurcharge = $distanceBlocks * 100; // 100 NOK per block
        }

        $difficultyCoefficient = $aggregatedDifficulty * $difficultyFactor;

        $totalBeforeSurcharges = $basePrice * $difficultyCoefficient;
        $expressSurcharge = $totalBeforeSurcharges * ($expressFactor - 1);

        $total = $totalBeforeSurcharges * $expressFactor + $distanceSurcharge;

        return new EcoDisposalEstimateDto(
            estimatedVolumeM3: round($estimatedVolume, 3),
            estimatedWeightKg: round($estimatedWeight, 3),
            basePriceNok: round($basePrice, 2),
            difficultyCoefficient: round($difficultyCoefficient, 2),
            expressSurchargeNok: round($expressSurcharge, 2),
            distanceSurchargeNok: round($distanceSurcharge, 2),
            totalPriceNok: round($total, 2),
        );
    }

    protected function calculateDifficultyFactor(
        ?int $floor,
        bool $hasElevator,
        ?int $parkingDistanceMeters
    ): float {
        $factor = 1.0;

        if ($floor !== null && $floor > 0) {
            if ($hasElevator) {
                $factor += max(0, ($floor - 1)) * 0.05; // each floor +5% with elevator
            } else {
                $factor += max(0, ($floor - 1)) * 0.10; // each floor +10% without elevator
            }
        }

        if ($parkingDistanceMeters !== null && $parkingDistanceMeters > 20) {
            $factor += min(0.5, ($parkingDistanceMeters - 20) / 100); // up to +50% for long carry
        }

        return max(1.0, $factor);
    }
}
