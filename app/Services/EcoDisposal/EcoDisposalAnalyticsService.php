<?php

namespace App\Services\EcoDisposal;

use App\Models\DisposalOrderDetails;
use App\Models\DisposalPartner;
use App\Models\EcoCertificate;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EcoDisposalAnalyticsService
{
    public function getSummary(Carbon $from, Carbon $to): array
    {
        if (! Schema::hasTable('orders')) {
            return $this->emptySummary();
        }

        $ordersQuery = Order::query()
            ->where('metadata->service_type', 'eco_disposal')
            ->whereBetween('created_at', [$from, $to]);

        $totalOrders = (clone $ordersQuery)->count();
        $completedOrders = (clone $ordersQuery)->where('status', 'completed')->count();
        $completionRate = $totalOrders > 0 ? round($completedOrders / $totalOrders * 100, 1) : 0.0;

        $detailsAgg = null;
        if (Schema::hasTable('disposal_order_details')) {
            $detailsAgg = DisposalOrderDetails::query()
                ->whereHas('order', function ($q) use ($from, $to) {
                    $q->where('metadata->service_type', 'eco_disposal')
                        ->whereBetween('created_at', [$from, $to]);
                })
                ->selectRaw('COALESCE(SUM(estimated_volume_m3),0) as total_volume_m3, COALESCE(SUM(estimated_weight_kg),0) as total_weight_kg')
                ->first();
        }

        $certAgg = null;
        if (Schema::hasTable('eco_certificates')) {
            $certAgg = EcoCertificate::query()
                ->whereHas('order', function ($q) use ($from, $to) {
                    $q->where('metadata->service_type', 'eco_disposal')
                        ->whereBetween('completed_at', [$from, $to]);
                })
                ->selectRaw('COALESCE(SUM(co2_saved_kg),0) as total_co2_saved_kg, COALESCE(SUM(items_reused_count),0) as total_items_reused')
                ->first();
        }

        return [
            'total_orders' => $totalOrders,
            'completed_orders' => $completedOrders,
            'completion_rate' => $completionRate,
            'total_volume_m3' => (float) ($detailsAgg->total_volume_m3 ?? 0),
            'total_weight_kg' => (float) ($detailsAgg->total_weight_kg ?? 0),
            'total_co2_saved_kg' => (float) ($certAgg->total_co2_saved_kg ?? 0),
            'total_items_reused' => (int) ($certAgg->total_items_reused ?? 0),
        ];
    }

    public function getTimeSeries(Carbon $from, Carbon $to, string $interval = 'day'): Collection
    {
        if (! Schema::hasTable('orders')) {
            return collect();
        }

        $rows = Order::query()
            ->where('metadata->service_type', 'eco_disposal')
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw("DATE(created_at) as d, COUNT(*) as orders_count, SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_count")
            ->groupBy('d')
            ->orderBy('d')
            ->get()
            ->keyBy('d');

        $certRows = collect();
        if (Schema::hasTable('eco_certificates')) {
            $certRows = EcoCertificate::query()
                ->whereHas('order', function ($q) {
                    $q->where('metadata->service_type', 'eco_disposal');
                })
                ->whereBetween('issued_at', [$from, $to])
                ->selectRaw('DATE(issued_at) as d, COALESCE(SUM(co2_saved_kg),0) as co2_saved_kg')
                ->groupBy('d')
                ->get()
                ->keyBy('d');
        }

        $points = [];
        $cursor = $from->copy()->startOfDay();
        while ($cursor <= $to) {
            $key = $cursor->toDateString();
            $orders = $rows->get($key);
            $cert = $certRows->get($key);
            $points[] = [
                'date' => $key,
                'orders_count' => (int) ($orders->orders_count ?? 0),
                'completed_count' => (int) ($orders->completed_count ?? 0),
                'co2_saved_kg' => (float) ($cert->co2_saved_kg ?? 0),
            ];
            $cursor->addDay();
        }

        return collect($points);
    }

    public function getTopPartners(Carbon $from, Carbon $to, int $limit = 10): Collection
    {
        if (
            ! Schema::hasTable('disposal_partners')
            || ! Schema::hasTable('disposal_order_details')
            || ! Schema::hasTable('orders')
        ) {
            return collect();
        }

        return DisposalPartner::query()
            ->select('disposal_partners.id', 'disposal_partners.name', 'disposal_partners.type')
            ->selectRaw('COUNT(DISTINCT disposal_order_details.order_id) as orders_count')
            ->selectRaw('COALESCE(SUM(disposal_order_details.estimated_volume_m3),0) as total_volume_m3')
            ->selectRaw('COALESCE(SUM(disposal_order_details.estimated_weight_kg),0) as total_weight_kg')
            ->selectRaw(
                Schema::hasTable('eco_certificates')
                    ? 'COALESCE(SUM(eco_certificates.co2_saved_kg),0) as total_co2_saved_kg'
                    : '0 as total_co2_saved_kg'
            )
            ->join('disposal_order_details', 'disposal_order_details.eco_partner_id', '=', 'disposal_partners.id')
            ->join('orders', 'orders.id', '=', 'disposal_order_details.order_id')
            ->when(
                Schema::hasTable('eco_certificates'),
                fn ($q) => $q->leftJoin('eco_certificates', 'eco_certificates.order_id', '=', 'orders.id')
            )
            ->where('orders.metadata->service_type', 'eco_disposal')
            ->whereBetween('orders.created_at', [$from, $to])
            ->groupBy('disposal_partners.id', 'disposal_partners.name', 'disposal_partners.type')
            ->orderByDesc('orders_count')
            ->limit($limit)
            ->get();
    }

    public function getCategoryBreakdown(Carbon $from, Carbon $to): Collection
    {
        if (! Schema::hasTable('disposal_order_details') || ! Schema::hasTable('orders')) {
            return collect();
        }

        $details = DisposalOrderDetails::query()
            ->whereHas('order', function ($q) use ($from, $to) {
                $q->where('metadata->service_type', 'eco_disposal')
                    ->whereBetween('created_at', [$from, $to]);
            })
            ->with('order')
            ->get();

        $categories = [];
        foreach ($details as $detail) {
            $items = is_array($detail->items) ? $detail->items : [];

            foreach ($items as $row) {
                $category = $row['category'] ?? null;
                if (! $category && isset($row['disposal_item_id']) && Schema::hasTable('disposal_items')) {
                    $category = DB::table('disposal_items')->where('id', $row['disposal_item_id'])->value('category');
                }

                if (! $category) {
                    $category = 'unknown';
                }

                $qty = (int) ($row['quantity'] ?? 1);
                if (! isset($categories[$category])) {
                    $categories[$category] = [
                        'category' => $category,
                        'orders_count' => 0,
                        'items_count' => 0,
                    ];
                }

                $categories[$category]['items_count'] += $qty;
                $categories[$category]['orders_count'] += 1;
            }
        }

        return collect(array_values($categories));
    }

    public function getZoneBreakdown(Carbon $from, Carbon $to): Collection
    {
        if (! Schema::hasTable('orders')) {
            return collect();
        }

        return Order::query()
            ->where('metadata->service_type', 'eco_disposal')
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw("COALESCE(metadata->>'zone_code', 'unknown') as zone_code")
            ->selectRaw('COUNT(*) as orders_count')
            ->selectRaw("SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_count")
            ->groupBy('zone_code')
            ->orderByDesc('orders_count')
            ->get();
    }

    protected function emptySummary(): array
    {
        return [
            'total_orders' => 0,
            'completed_orders' => 0,
            'completion_rate' => 0.0,
            'total_volume_m3' => 0.0,
            'total_weight_kg' => 0.0,
            'total_co2_saved_kg' => 0.0,
            'total_items_reused' => 0,
        ];
    }
}

