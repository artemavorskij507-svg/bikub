<?php

namespace App\Console\Commands;

use App\Services\Scheduling\SlotReservationService;
use Illuminate\Console\Command;

class SlotsExpireHolds extends Command
{
    protected $signature = 'slots:expire-holds';

    protected $description = 'Expire slot holds that passed TTL';

    public function handle(SlotReservationService $svc): int
    {
        $n = $svc->expireHolds();
        $this->info("Expired holds: {$n}");

        return self::SUCCESS;
    }
}
