<?php

namespace App\Listeners;

use App\Events\ClaimRejected;
use App\Events\ClaimResolved;
use App\Services\Handyman\HandymanKpiService;

class RecalculateHandymanKpiForClaim
{
    public function __construct(
        protected HandymanKpiService $kpiService
    ) {}

    public function handle(ClaimResolved|ClaimRejected $event): void
    {
        $order = $event->claim->order;

        if (! $order) {
            return;
        }

        $order->load('handymanAssignments.executorProfile');

        foreach ($order->handymanAssignments as $assignment) {
            if ($assignment->executorProfile) {
                $this->kpiService->recalculateForExecutor($assignment->executorProfile);
            }
        }
    }
}
