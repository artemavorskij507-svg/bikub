<?php

namespace App\Console\Commands;

use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AnalyticsRebuildSnapshots extends Command
{
    protected $signature = 'analytics:rebuild-snapshots {--from=} {--to=}';

    protected $description = 'Rebuild analytics daily snapshots in range';

    public function handle(): int
    {
        $from = CarbonImmutable::parse($this->option('from') ?: 'yesterday')->startOfDay();
        $to = CarbonImmutable::parse($this->option('to') ?: 'today')->startOfDay();

        for ($d = $from; $d->lte($to); $d = $d->addDay()) {
            $this->buildForDate($d->toDateString());
        }
        $this->info('Snapshots rebuilt');

        return self::SUCCESS;
    }

    protected function buildForDate(string $date): void
    {
        // Orders/tasks aggregations (simplified, replace with real logic)
        $orders = DB::table('orders')
            ->selectRaw("coalesce(zone_id, '') as zone_id, coalesce(category, 'unknown') as category, count(*) as cnt, sum(amount_minor) as revenue")
            ->whereDate('created_at', $date)
            ->groupBy('zone_id', 'category')
            ->get();

        foreach ($orders as $row) {
            DB::table('daily_metrics')->updateOrInsert(
                ['date' => $date, 'zone_id' => $row->zone_id, 'category' => $row->category],
                [
                    'orders_cnt' => (int) $row->cnt,
                    'orders_revenue_cents' => (int) ($row->revenue ?? 0),
                    'aov_cents' => $row->cnt ? (int) floor(($row->revenue ?? 0) / $row->cnt) : 0,
                ]
            );
        }

        // Slots (simplified)
        $slots = DB::table('schedule_slots')
            ->selectRaw("coalesce(zone_id, '') as zone_id, kind as slot_kind, strftime('%H', start_at) as hour, sum(capacity_total) as capacity")
            ->whereDate('start_at', $date)
            ->groupBy('zone_id', 'slot_kind', 'hour')
            ->get();
        foreach ($slots as $s) {
            DB::table('slot_stats_daily')->updateOrInsert(
                ['date' => $date, 'zone_id' => $s->zone_id, 'slot_kind' => $s->slot_kind, 'hour' => (int) $s->hour],
                [
                    'capacity' => (int) $s->capacity,
                ]
            );
        }
    }
}
