<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\SlaPolicy;
use App\Services\SlaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SlaController extends Controller
{
    protected SlaService $slaService;

    public function __construct(SlaService $slaService)
    {
        $this->slaService = $slaService;
    }

    /**
     * Get SLA policies.
     */
    public function getPolicies(Request $request)
    {
        $policies = SlaPolicy::where('is_active', true)->get();

        return response()->json([
            'success' => true,
            'data' => $policies->map(function ($policy) {
                return [
                    'id' => $policy->id,
                    'name' => $policy->name,
                    'code' => $policy->code,
                    'base_minutes' => $policy->base_minutes,
                    'night_coef' => $policy->night_coef,
                    'snow_coef' => $policy->snow_coef,
                    'overload_coef' => $policy->overload_coef,
                    'conditions' => $policy->conditions,
                ];
            }),
        ]);
    }

    /**
     * Create SLA policy.
     */
    public function createPolicy(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:sla_policies,code',
            'base_minutes' => 'required|integer|min:1',
            'night_coef' => 'required|numeric|min:1.0|max:3.0',
            'snow_coef' => 'required|numeric|min:1.0|max:3.0',
            'overload_coef' => 'required|numeric|min:1.0|max:3.0',
            'conditions' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 400);
        }

        $policy = SlaPolicy::create($request->all());

        return response()->json([
            'success' => true,
            'data' => $policy,
            'message' => 'SLA policy created successfully',
        ], 201);
    }

    /**
     * Update SLA policy.
     */
    public function updatePolicy(Request $request, string $id)
    {
        $policy = SlaPolicy::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'code' => 'sometimes|string|max:50|unique:sla_policies,code,'.$id,
            'base_minutes' => 'sometimes|integer|min:1',
            'night_coef' => 'sometimes|numeric|min:1.0|max:3.0',
            'snow_coef' => 'sometimes|numeric|min:1.0|max:3.0',
            'overload_coef' => 'sometimes|numeric|min:1.0|max:3.0',
            'conditions' => 'nullable|array',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 400);
        }

        $policy->update($request->all());

        return response()->json([
            'success' => true,
            'data' => $policy,
            'message' => 'SLA policy updated successfully',
        ]);
    }

    /**
     * Calculate SLA for order.
     */
    public function calculateSla(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 400);
        }

        $order = Order::findOrFail($request->order_id);
        $deadline = $this->slaService->calculateSlaDeadline($order);

        return response()->json([
            'success' => true,
            'data' => [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'sla_deadline' => $deadline,
                'time_to_deadline' => now()->diffInMinutes($deadline, false),
                'breach_risk' => $order->sla_breach_risk,
                'weather_conditions' => $order->weather_conditions,
            ],
            'message' => 'SLA calculated successfully',
        ]);
    }

    /**
     * Get orders at risk.
     */
    public function getOrdersAtRisk(Request $request)
    {
        $atRiskOrders = $this->slaService->getOrdersAtRisk();

        return response()->json([
            'success' => true,
            'data' => $atRiskOrders,
            'count' => count($atRiskOrders),
        ]);
    }

    /**
     * Generate SLA alerts.
     */
    public function generateAlerts(Request $request)
    {
        $alerts = $this->slaService->generateSlaAlerts();

        return response()->json([
            'success' => true,
            'data' => $alerts,
            'count' => count($alerts),
            'message' => 'SLA alerts generated successfully',
        ]);
    }

    /**
     * Get SLA metrics.
     */
    public function getMetrics(Request $request)
    {
        $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date|after:from',
        ]);

        $from = $request->from ?? now()->subDays(30);
        $to = $request->to ?? now();

        $orders = Order::whereBetween('created_at', [$from, $to])->get();

        $metrics = [
            'total_orders' => $orders->count(),
            'breached_orders' => $orders->where('sla_deadline', '<', now())->count(),
            'breach_rate' => 0,
            'average_breach_time' => 0,
            'weather_impact' => $this->calculateWeatherImpact($orders),
            'time_distribution' => $this->calculateTimeDistribution($orders),
            'slot_overload_impact' => $this->calculateSlotOverloadImpact($orders),
        ];

        if ($metrics['total_orders'] > 0) {
            $metrics['breach_rate'] = $metrics['breached_orders'] / $metrics['total_orders'];
        }

        $breachedOrders = $orders->where('sla_deadline', '<', now());
        if ($breachedOrders->count() > 0) {
            $metrics['average_breach_time'] = $breachedOrders->avg(function ($order) {
                return $order->sla_deadline->diffInMinutes($order->completed_at ?? now());
            });
        }

        return response()->json([
            'success' => true,
            'data' => $metrics,
            'period' => [
                'from' => $from,
                'to' => $to,
            ],
        ]);
    }

    /**
     * Update SLA policies based on performance.
     */
    public function updatePolicies(Request $request)
    {
        $this->slaService->updateSlaPolicies();

        return response()->json([
            'success' => true,
            'message' => 'SLA policies updated based on performance',
        ]);
    }

    /**
     * Calculate weather impact on SLA.
     */
    private function calculateWeatherImpact($orders): array
    {
        $weatherImpact = [
            'clear' => 0,
            'rain' => 0,
            'snow' => 0,
            'fog' => 0,
        ];

        foreach ($orders as $order) {
            if ($order->weather_conditions) {
                $condition = $order->weather_conditions['condition'] ?? 'clear';
                $weatherImpact[$condition] = ($weatherImpact[$condition] ?? 0) + 1;
            }
        }

        return $weatherImpact;
    }

    /**
     * Calculate time distribution of orders.
     */
    private function calculateTimeDistribution($orders): array
    {
        $timeDistribution = [
            'morning' => 0,    // 06:00 - 12:00
            'afternoon' => 0,  // 12:00 - 18:00
            'evening' => 0,    // 18:00 - 22:00
            'night' => 0,      // 22:00 - 06:00
        ];

        foreach ($orders as $order) {
            $hour = $order->created_at->hour;

            if ($hour >= 6 && $hour < 12) {
                $timeDistribution['morning']++;
            } elseif ($hour >= 12 && $hour < 18) {
                $timeDistribution['afternoon']++;
            } elseif ($hour >= 18 && $hour < 22) {
                $timeDistribution['evening']++;
            } else {
                $timeDistribution['night']++;
            }
        }

        return $timeDistribution;
    }

    /**
     * Calculate slot overload impact.
     */
    private function calculateSlotOverloadImpact($orders): array
    {
        $overloadImpact = [
            'normal' => 0,     // < 80% capacity
            'high' => 0,       // 80-90% capacity
            'critical' => 0,   // > 90% capacity
        ];

        foreach ($orders as $order) {
            if ($order->scheduleSlot) {
                $slot = $order->scheduleSlot;
                $overload = $slot->capacity > 0 ? $slot->booked / $slot->capacity : 0;

                if ($overload < 0.8) {
                    $overloadImpact['normal']++;
                } elseif ($overload < 0.9) {
                    $overloadImpact['high']++;
                } else {
                    $overloadImpact['critical']++;
                }
            }
        }

        return $overloadImpact;
    }
}
