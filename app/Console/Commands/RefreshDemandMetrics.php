<?php

namespace App\Console\Commands;

use App\Models\PriceEstimateLog;
use App\Services\Pricing\DemandService;
use Illuminate\Console\Command;

class RefreshDemandMetrics extends Command
{
    protected $signature = 'pricing:demand:refresh {--minutes=5 : Lookback window in minutes}';

    protected $description = 'Aggregates pricing demand metrics per zone and stores them in cache.';

    public function handle(DemandService $demandService): int
    {
        $minutes = max(1, (int) $this->option('minutes'));
        $threshold = now()->subMinutes($minutes);

        $this->info("Aggregating demand metrics for last {$minutes} minute(s)...");

        $zones = PriceEstimateLog::query()
            ->selectRaw('COALESCE(NULLIF(zone, \'\'), \'narvik\') as zone, COUNT(*) as total')
            ->where('created_at', '>=', $threshold)
            ->groupBy('zone')
            ->orderByDesc('total')
            ->get();

        if ($zones->isEmpty()) {
            $this->warn('No price estimate logs found for the selected window.');

            return self::SUCCESS;
        }

        $zones->each(function ($row) use ($demandService, $minutes) {
            $zone = $row->zone ?? 'narvik';
            $rpm = (int) ceil($row->total / $minutes);

            $demandService->storeMetrics($zone, [
                'requests_per_minute' => $rpm,
                'active_orders' => $row->total,
            ]);

            $this->line("  • {$zone}: {$rpm} rpm");
        });

        $this->info('Demand metrics cached.');

        return self::SUCCESS;
    }
}
