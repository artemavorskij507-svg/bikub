<?php

namespace App\Http\Controllers;

use App\Models\NotificationEvent;
use App\Models\Order;
use App\Models\Partner;
use App\Models\RouteMatrix;
use App\Models\Task;
use Illuminate\Http\Request;

class AnalyticsV2Controller extends Controller
{
    /**
     * Get comprehensive dashboard metrics.
     */
    public function getDashboardMetrics(Request $request)
    {
        $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date|after:from',
            'partner_id' => 'nullable|exists:partners,id',
        ]);

        $from = $request->from ?? now()->subDays(30);
        $to = $request->to ?? now();

        $query = Order::whereBetween('created_at', [$from, $to]);

        if ($request->partner_id) {
            $query->where('partner_id', $request->partner_id);
        }

        $orders = $query->get();

        return response()->json([
            'success' => true,
            'data' => [
                'overview' => $this->getOverviewMetrics($orders),
                'sla_metrics' => $this->getSlaMetrics($orders),
                'eta_accuracy' => $this->getEtaAccuracyMetrics($orders),
                'aht_metrics' => $this->getAhtMetricsForDashboard($orders),
                'slot_utilization' => $this->getSlotUtilizationMetrics($orders),
                'partner_revenue' => $this->getPartnerRevenueMetrics($orders),
                'weather_impact' => $this->getWeatherImpactMetrics($orders),
                'notification_metrics' => $this->getNotificationMetrics($from, $to),
                'geo_metrics' => $this->getGeoMetrics($orders),
            ],
            'period' => [
                'from' => $from,
                'to' => $to,
            ],
        ]);
    }

    /**
     * Get SLA breach analysis.
     */
    public function getSlaBreachAnalysis(Request $request)
    {
        $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date|after:from',
            'group_by' => 'nullable|in:module,zone,slot,partner',
        ]);

        $from = $request->from ?? now()->subDays(30);
        $to = $request->to ?? now();
        $groupBy = $request->group_by ?? 'module';

        $orders = Order::whereBetween('created_at', [$from, $to])
            ->with(['orderItems.serviceType', 'scheduleSlot', 'partner'])
            ->get();

        $breachAnalysis = $this->analyzeSlaBreaches($orders, $groupBy);

        return response()->json([
            'success' => true,
            'data' => $breachAnalysis,
            'group_by' => $groupBy,
            'period' => [
                'from' => $from,
                'to' => $to,
            ],
        ]);
    }

    /**
     * Get ETA accuracy metrics.
     */
    public function getEtaAccuracy(Request $request)
    {
        $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date|after:from',
        ]);

        $from = $request->from ?? now()->subDays(30);
        $to = $request->to ?? now();

        $tasks = Task::whereBetween('created_at', [$from, $to])
            ->whereNotNull('completed_at')
            ->with(['order'])
            ->get();

        $etaMetrics = $this->calculateEtaAccuracy($tasks);

        return response()->json([
            'success' => true,
            'data' => $etaMetrics,
            'period' => [
                'from' => $from,
                'to' => $to,
            ],
        ]);
    }

    /**
     * Get AHT (Average Handling Time) metrics.
     */
    public function getAhtMetrics(Request $request)
    {
        $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date|after:from',
            'courier_id' => 'nullable|exists:users,id',
        ]);

        $from = $request->from ?? now()->subDays(30);
        $to = $request->to ?? now();

        $query = Task::whereBetween('created_at', [$from, $to])
            ->whereNotNull('completed_at')
            ->whereNotNull('assigned_at');

        if ($request->courier_id) {
            $query->where('assignee_id', $request->courier_id);
        }

        $tasks = $query->with(['assignee', 'orderItem.serviceType'])->get();

        $ahtMetrics = $this->calculateAhtMetrics($tasks);

        return response()->json([
            'success' => true,
            'data' => $ahtMetrics,
            'period' => [
                'from' => $from,
                'to' => $to,
            ],
        ]);
    }

    /**
     * Export analytics data.
     */
    public function exportData(Request $request)
    {
        $request->validate([
            'type' => 'required|in:orders,tasks,sla,eta,aht,revenue',
            'format' => 'required|in:csv,xlsx',
            'from' => 'nullable|date',
            'to' => 'nullable|date|after:from',
            'partner_id' => 'nullable|exists:partners,id',
        ]);

        $from = $request->from ?? now()->subDays(30);
        $to = $request->to ?? now();

        $data = $this->prepareExportData($request->type, $from, $to, $request->partner_id);
        $filename = $this->generateExportFilename($request->type, $from, $to);

        if ($request->format === 'csv') {
            return $this->exportCsv($data, $filename);
        } else {
            return $this->exportXlsx($data, $filename);
        }
    }

    /**
     * Get saved reports.
     *
     * Note: Saved reports functionality is not yet implemented.
     * This endpoint returns an empty array until the feature is fully implemented.
     */
    public function getSavedReports(Request $request)
    {
        // TODO: Implement saved reports functionality
        // This would require:
        // 1. A database table for saved reports (analytics_saved_reports)
        // 2. A model (AnalyticsSavedReport)
        // 3. CRUD operations for saving/loading reports

        return response()->json([
            'success' => true,
            'data' => [],
            'message' => 'Saved reports feature is not yet implemented',
        ]);
    }

    /**
     * Get overview metrics.
     */
    private function getOverviewMetrics($orders): array
    {
        return [
            'total_orders' => $orders->count(),
            'completed_orders' => $orders->where('status', 'completed')->count(),
            'pending_orders' => $orders->where('status', 'pending')->count(),
            'cancelled_orders' => $orders->where('status', 'cancelled')->count(),
            'total_revenue' => $orders->sum('total_amount'),
            'average_order_value' => $orders->avg('total_amount'),
            'completion_rate' => $orders->count() > 0 ? $orders->where('status', 'completed')->count() / $orders->count() : 0,
        ];
    }

    /**
     * Get SLA metrics.
     */
    private function getSlaMetrics($orders): array
    {
        $totalOrders = $orders->count();
        $breachedOrders = $orders->where('sla_deadline', '<', now())->count();

        return [
            'total_orders' => $totalOrders,
            'breached_orders' => $breachedOrders,
            'breach_rate' => $totalOrders > 0 ? $breachedOrders / $totalOrders : 0,
            'average_breach_time' => $this->calculateAverageBreachTime($orders),
            'breach_trend' => $this->calculateBreachTrend($orders),
        ];
    }

    /**
     * Get ETA accuracy metrics.
     */
    private function getEtaAccuracyMetrics($orders): array
    {
        $tasks = Task::whereIn('order_id', $orders->pluck('id'))
            ->whereNotNull('completed_at')
            ->get();

        return $this->calculateEtaAccuracy($tasks);
    }

    /**
     * Get AHT metrics for dashboard.
     */
    private function getAhtMetricsForDashboard($orders): array
    {
        $tasks = Task::whereIn('order_id', $orders->pluck('id'))
            ->whereNotNull('completed_at')
            ->whereNotNull('assigned_at')
            ->get();

        return $this->calculateAhtMetrics($tasks);
    }

    /**
     * Get slot utilization metrics.
     */
    private function getSlotUtilizationMetrics($orders): array
    {
        $slotUtilization = [];

        foreach ($orders->groupBy('schedule_slot_id') as $slotId => $slotOrders) {
            $slot = \App\Models\ScheduleSlot::find($slotId);
            if ($slot) {
                $slotUtilization[] = [
                    'slot_name' => $slot->name,
                    'capacity' => $slot->capacity,
                    'booked' => $slotOrders->count(),
                    'utilization_rate' => $slot->capacity > 0 ? $slotOrders->count() / $slot->capacity : 0,
                    'overload_rate' => $slot->capacity > 0 ? max(0, ($slotOrders->count() - $slot->capacity) / $slot->capacity) : 0,
                ];
            }
        }

        return $slotUtilization;
    }

    /**
     * Get partner revenue metrics.
     */
    private function getPartnerRevenueMetrics($orders): array
    {
        $partnerRevenue = [];

        foreach ($orders->groupBy('partner_id') as $partnerId => $partnerOrders) {
            $partner = Partner::find($partnerId);
            if ($partner) {
                $partnerRevenue[] = [
                    'partner_name' => $partner->name,
                    'total_orders' => $partnerOrders->count(),
                    'total_revenue' => $partnerOrders->sum('total_amount'),
                    'average_order_value' => $partnerOrders->avg('total_amount'),
                    'completion_rate' => $partnerOrders->where('status', 'completed')->count() / $partnerOrders->count(),
                ];
            }
        }

        return $partnerRevenue;
    }

    /**
     * Get weather impact metrics.
     */
    private function getWeatherImpactMetrics($orders): array
    {
        $weatherImpact = [
            'clear' => ['orders' => 0, 'breaches' => 0],
            'rain' => ['orders' => 0, 'breaches' => 0],
            'snow' => ['orders' => 0, 'breaches' => 0],
            'fog' => ['orders' => 0, 'breaches' => 0],
        ];

        foreach ($orders as $order) {
            if ($order->weather_conditions) {
                $condition = $order->weather_conditions['condition'] ?? 'clear';
                $weatherImpact[$condition]['orders']++;

                if ($order->sla_deadline && $order->sla_deadline < now()) {
                    $weatherImpact[$condition]['breaches']++;
                }
            }
        }

        return $weatherImpact;
    }

    /**
     * Get notification metrics.
     */
    private function getNotificationMetrics($from, $to): array
    {
        $notifications = NotificationEvent::whereBetween('created_at', [$from, $to])->get();

        return [
            'total_sent' => $notifications->where('status', 'sent')->count(),
            'total_failed' => $notifications->where('status', 'failed')->count(),
            'success_rate' => $notifications->count() > 0 ? $notifications->where('status', 'sent')->count() / $notifications->count() : 0,
            'by_channel' => $notifications->groupBy('channel')->map(function ($channelNotifications) {
                return [
                    'sent' => $channelNotifications->where('status', 'sent')->count(),
                    'failed' => $channelNotifications->where('status', 'failed')->count(),
                ];
            }),
        ];
    }

    /**
     * Get geo metrics.
     */
    private function getGeoMetrics($orders): array
    {
        $routeMatrices = RouteMatrix::whereBetween('created_at', [
            $orders->min('created_at'),
            $orders->max('created_at'),
        ])->get();

        return [
            'total_routes_calculated' => $routeMatrices->count(),
            'average_distance' => $routeMatrices->avg('distance_meters'),
            'average_duration' => $routeMatrices->avg('duration_seconds'),
            'cache_hit_rate' => $this->calculateCacheHitRate($routeMatrices),
        ];
    }

    /**
     * Calculate ETA accuracy.
     */
    private function calculateEtaAccuracy($tasks): array
    {
        $etaErrors = [];

        foreach ($tasks as $task) {
            if ($task->order && $task->order->scheduled_at) {
                $estimated = $task->order->scheduled_at;
                $actual = $task->completed_at;

                if ($actual) {
                    $error = $actual->diffInMinutes($estimated);
                    $etaErrors[] = $error;
                }
            }
        }

        if (empty($etaErrors)) {
            return [
                'mae' => 0,
                'medae' => 0,
                'accuracy_rate' => 0,
                'total_samples' => 0,
            ];
        }

        $mae = array_sum($etaErrors) / count($etaErrors);
        $medae = $this->calculateMedian($etaErrors);
        $accuracyRate = count(array_filter($etaErrors, fn ($error) => abs($error) <= 15)) / count($etaErrors);

        return [
            'mae' => round($mae, 2),
            'medae' => round($medae, 2),
            'accuracy_rate' => round($accuracyRate, 2),
            'total_samples' => count($etaErrors),
        ];
    }

    /**
     * Calculate AHT metrics.
     */
    private function calculateAhtMetrics($tasks): array
    {
        $ahtValues = [];

        foreach ($tasks as $task) {
            if ($task->assigned_at && $task->completed_at) {
                $aht = $task->assigned_at->diffInMinutes($task->completed_at);
                $ahtValues[] = $aht;
            }
        }

        if (empty($ahtValues)) {
            return [
                'average_aht' => 0,
                'median_aht' => 0,
                'min_aht' => 0,
                'max_aht' => 0,
                'total_tasks' => 0,
            ];
        }

        return [
            'average_aht' => round(array_sum($ahtValues) / count($ahtValues), 2),
            'median_aht' => round($this->calculateMedian($ahtValues), 2),
            'min_aht' => min($ahtValues),
            'max_aht' => max($ahtValues),
            'total_tasks' => count($ahtValues),
        ];
    }

    /**
     * Calculate median value.
     */
    private function calculateMedian(array $values): float
    {
        sort($values);
        $count = count($values);

        if ($count % 2 === 0) {
            return ($values[$count / 2 - 1] + $values[$count / 2]) / 2;
        } else {
            return $values[intval($count / 2)];
        }
    }

    /**
     * Calculate average breach time.
     */
    private function calculateAverageBreachTime($orders): float
    {
        $breachedOrders = $orders->where('sla_deadline', '<', now());

        if ($breachedOrders->isEmpty()) {
            return 0;
        }

        $totalBreachTime = 0;
        foreach ($breachedOrders as $order) {
            $totalBreachTime += $order->sla_deadline->diffInMinutes($order->completed_at ?? now());
        }

        return round($totalBreachTime / $breachedOrders->count(), 2);
    }

    /**
     * Calculate breach trend.
     */
    private function calculateBreachTrend($orders): array
    {
        $trend = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $dayOrders = $orders->filter(function ($order) use ($date) {
                return $order->created_at->isSameDay($date);
            });

            $breachRate = $dayOrders->count() > 0
                ? $dayOrders->where('sla_deadline', '<', now())->count() / $dayOrders->count()
                : 0;

            $trend[] = [
                'date' => $date->format('Y-m-d'),
                'breach_rate' => round($breachRate, 2),
            ];
        }

        return $trend;
    }

    /**
     * Calculate cache hit rate.
     */
    private function calculateCacheHitRate($routeMatrices): float
    {
        $total = $routeMatrices->count();
        $cached = $routeMatrices->where('cached_at', '>', now()->subHour())->count();

        return $total > 0 ? round($cached / $total, 2) : 0;
    }

    /**
     * Prepare export data.
     */
    private function prepareExportData(string $type, $from, $to, $partnerId = null): array
    {
        $query = match ($type) {
            'orders' => Order::whereBetween('created_at', [$from, $to]),
            'tasks' => Task::whereBetween('created_at', [$from, $to]),
            default => Order::whereBetween('created_at', [$from, $to]),
        };

        if ($partnerId) {
            $query->where('partner_id', $partnerId);
        }

        return $query->get()->toArray();
    }

    /**
     * Generate export filename.
     */
    private function generateExportFilename(string $type, $from, $to): string
    {
        return "analytics_{$type}_".$from->format('Y-m-d').'_to_'.$to->format('Y-m-d').'.csv';
    }

    /**
     * Export CSV data.
     */
    private function exportCsv(array $data, string $filename)
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        return response()->stream(function () use ($data) {
            $file = fopen('php://output', 'w');

            if (! empty($data)) {
                fputcsv($file, array_keys($data[0]));
                foreach ($data as $row) {
                    fputcsv($file, $row);
                }
            }

            fclose($file);
        }, 200, $headers);
    }

    /**
     * Export XLSX data.
     */
    private function exportXlsx(array $data, string $filename)
    {
        // Placeholder for XLSX export
        // Would require PhpSpreadsheet package
        return response()->json([
            'success' => false,
            'message' => 'XLSX export not implemented yet',
        ], 501);
    }

    /**
     * Analyze SLA breaches by group.
     */
    private function analyzeSlaBreaches($orders, string $groupBy): array
    {
        $groupedOrders = match ($groupBy) {
            'module' => $orders->groupBy('orderItems.serviceType.category.name'),
            'zone' => $orders->groupBy('geo_zone_id'),
            'slot' => $orders->groupBy('schedule_slot_id'),
            'partner' => $orders->groupBy('partner_id'),
            default => $orders->groupBy('orderItems.serviceType.category.name'),
        };

        $analysis = [];

        foreach ($groupedOrders as $groupKey => $groupOrders) {
            $totalOrders = $groupOrders->count();
            $breachedOrders = $groupOrders->where('sla_deadline', '<', now())->count();

            $analysis[] = [
                'group' => $groupKey,
                'total_orders' => $totalOrders,
                'breached_orders' => $breachedOrders,
                'breach_rate' => $totalOrders > 0 ? round($breachedOrders / $totalOrders, 2) : 0,
                'average_breach_time' => $this->calculateAverageBreachTime($groupOrders),
            ];
        }

        return $analysis;
    }
}
