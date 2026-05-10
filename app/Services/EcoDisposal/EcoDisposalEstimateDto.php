<?php

namespace App\Services\EcoDisposal;

class EcoDisposalEstimateDto
{
    public function __construct(
        public float $estimatedVolumeM3,
        public float $estimatedWeightKg,
        public float $basePriceNok,
        public float $difficultyCoefficient,
        public float $expressSurchargeNok,
        public float $distanceSurchargeNok,
        public float $totalPriceNok,
    ) {}
}
