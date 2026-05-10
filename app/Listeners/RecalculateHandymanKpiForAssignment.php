<?php

namespace App\Listeners;

use App\Events\HandymanJobCompleted;
use App\Services\Handyman\HandymanKpiService;

class RecalculateHandymanKpiForAssignment
{
    public function __construct(
        protected HandymanKpiService $kpiService
    ) {}

    public function handle(HandymanJobCompleted $event): void
    {
        $executor = $event->assignment->executorProfile;

        if (! $executor) {
            return;
        }

        $this->kpiService->recalculateForExecutor($executor);
    }
}
