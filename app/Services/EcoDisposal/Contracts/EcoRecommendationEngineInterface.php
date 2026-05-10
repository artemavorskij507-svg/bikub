<?php

namespace App\Services\EcoDisposal\Contracts;

use App\Models\DisposalPartner;
use App\Models\EcoTeam;
use App\Models\Order;
use Carbon\Carbon;

class EcoTimeslotDto
{
    public function __construct(
        public Carbon $start,
        public Carbon $end,
        public ?string $zoneCode = null,
    ) {}
}

interface EcoRecommendationEngineInterface
{
    public function recommendPartnerForOrder(Order $order): ?DisposalPartner;

    public function recommendTeamForOrder(Order $order): ?EcoTeam;

    public function recommendTimeslotForOrder(Order $order): ?EcoTimeslotDto;
}
