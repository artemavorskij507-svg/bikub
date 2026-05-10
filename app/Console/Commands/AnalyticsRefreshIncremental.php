<?php

namespace App\Console\Commands;

use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class AnalyticsRefreshIncremental extends Command
{
    protected $signature = 'analytics:refresh-incremental';

    protected $description = 'Refresh analytics snapshots for recent period';

    public function handle(): int
    {
        $from = CarbonImmutable::now()->subHours(6)->startOfHour();
        $to = CarbonImmutable::now()->startOfHour();
        for ($d = $from; $d->lte($to); $d = $d->addHour()) {
            $this->refreshForDate($d->toDateString());
        }
        $this->info('Incremental refreshed');

        return self::SUCCESS;
    }

    protected function refreshForDate(string $date): void
    {
        // Delegate to rebuild for the date (simple approach)
        (new AnalyticsRebuildSnapshots)->handle();
    }
}
