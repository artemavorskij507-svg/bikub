<?php

namespace App\Services;

use App\Models\Order;
use App\Models\SlaPolicy;
use App\Models\Task;
use App\Models\WeatherData;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class SlaService
{
    /**
     * Calculate SLA deadline for order.
     */
    public function calculateSlaDeadline(Order $order): Carbon
    {
        $slaPolicy = $order->slaPolicy ?? $this->getDefaultSlaPolicy();
        $baseMinutes = $slaPolicy->base_minutes;

        // Apply coefficients
        $adjustedMinutes = $this->applyCoefficients($baseMinutes, $order, $slaPolicy);

        // Calculate deadline
        $deadline = $order->created_at->addMinutes($adjustedMinutes);

        // Update order with SLA info
        $order->update([
            'sla_deadline' => $deadline,
            'sla_breach_risk' => $this->assessBreachRisk($order, $deadline),
            'weather_conditions' => $this->getWeatherConditions($order),
        ]);

        return $deadline;
    }

    /**
     * Apply weather and other coefficients to SLA.
     */
    private function applyCoefficients(int $baseMinutes, Order $order, SlaPolicy $slaPolicy): int
    {
        $adjustedMinutes = $baseMinutes;

        // Night coefficient (22:00 - 06:00)
        if ($this->isNightTime($order->created_at)) {
            $adjustedMinutes = (int) ($adjustedMinutes * $slaPolicy->night_coef);
        }

        // Weather coefficient
        $weather = $this->getCurrentWeather($order);
        if ($weather && $this->isBadWeather($weather)) {
            $adjustedMinutes = (int) ($adjustedMinutes * $slaPolicy->snow_coef);
        }

        // Slot overload coefficient
        $slotOverload = $this->getSlotOverload($order);
        if ($slotOverload > 0.8) { // 80% capacity
            $adjustedMinutes = (int) ($adjustedMinutes * $slaPolicy->overload_coef);
        }

        return $adjustedMinutes;
    }

    /**
     * Assess breach risk for order.
     */
    public function assessBreachRisk(Order $order, Carbon $deadline): bool
    {
        $now = now();
        $timeToDeadline = $now->diffInMinutes($deadline, false);

        // High risk if less than 30 minutes to deadline
        if ($timeToDeadline < 30) {
            return true;
        }

        // Check if order is stuck in pending status for too long
        if ($order->status === 'pending' && $order->created_at->diffInMinutes($now) > 60) {
            return true;
        }

        // Check if task is assigned but not started
        $task = $order->tasks()->where('status', 'assigned')->first();
        if ($task && $task->assigned_at && $task->assigned_at->diffInMinutes($now) > 30) {
            return true;
        }

        return false;
    }

    /**
     * Get orders at risk of SLA breach.
     */
    public function getOrdersAtRisk(): array
    {
        $atRiskOrders = Order::where('sla_breach_risk', true)
            ->whereIn('status', ['pending', 'confirmed', 'in_progress'])
            ->with(['user', 'tasks.assignee'])
            ->get();

        return $atRiskOrders->map(function ($order) {
            return [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $order->status,
                'sla_deadline' => $order->sla_deadline,
                'time_to_deadline' => now()->diffInMinutes($order->sla_deadline, false),
                'customer_name' => $order->user->name,
                'assigned_courier' => $order->tasks->first()?->assignee?->name,
                'weather_impact' => $order->weather_conditions,
                'priority' => $this->calculatePriority($order),
            ];
        })->toArray();
    }

    /**
     * Calculate priority score for order.
     */
    private function calculatePriority(Order $order): int
    {
        $priority = 1; // Base priority

        // Time urgency
        $timeToDeadline = now()->diffInMinutes($order->sla_deadline, false);
        if ($timeToDeadline < 15) {
            $priority += 3; // Critical
        } elseif ($timeToDeadline < 30) {
            $priority += 2; // High
        } elseif ($timeToDeadline < 60) {
            $priority += 1; // Medium
        }

        // Weather impact
        if ($order->weather_conditions && $this->isBadWeather($order->weather_conditions)) {
            $priority += 1;
        }

        // Slot overload
        $slotOverload = $this->getSlotOverload($order);
        if ($slotOverload > 0.9) {
            $priority += 2;
        } elseif ($slotOverload > 0.8) {
            $priority += 1;
        }

        return min($priority, 5); // Max priority 5
    }

    /**
     * Get weather conditions for order.
     */
    private function getWeatherConditions(Order $order): ?array
    {
        if (! $order->address) {
            return null;
        }

        $locationCode = $this->getLocationCode($order->address);

        return $this->getCurrentWeather($order, $locationCode);
    }

    /**
     * Get current weather for location.
     */
    private function getCurrentWeather(Order $order, ?string $locationCode = null): ?array
    {
        if (! $locationCode) {
            $locationCode = $this->getLocationCode($order->address);
        }

        $cacheKey = "weather_{$locationCode}_".now()->format('Y-m-d-H');

        return Cache::remember($cacheKey, 3600, function () use ($locationCode) {
            $weather = WeatherData::where('location_code', $locationCode)
                ->where('date', now()->toDateString())
                ->where('time', '>=', now()->subHour())
                ->orderBy('time', 'desc')
                ->first();

            if (! $weather) {
                // Fetch from external API (placeholder)
                return $this->fetchWeatherFromApi($locationCode);
            }

            return [
                'temperature' => $weather->temperature,
                'humidity' => $weather->humidity,
                'wind_speed' => $weather->wind_speed,
                'precipitation' => $weather->precipitation,
                'condition' => $weather->condition,
            ];
        });
    }

    /**
     * Check if weather is bad for delivery.
     */
    private function isBadWeather(?array $weather): bool
    {
        if (! $weather) {
            return false;
        }

        // Snow, heavy rain, or strong wind
        return in_array($weather['condition'], ['snow', 'heavy_rain']) ||
               $weather['wind_speed'] > 50 || // km/h
               $weather['precipitation'] > 10; // mm
    }

    /**
     * Check if current time is night.
     */
    private function isNightTime(Carbon $time): bool
    {
        $hour = $time->hour;

        return $hour >= 22 || $hour < 6;
    }

    /**
     * Get slot overload percentage.
     */
    private function getSlotOverload(Order $order): float
    {
        if (! $order->scheduleSlot) {
            return 0;
        }

        $slot = $order->scheduleSlot;
        if ($slot->capacity <= 0) {
            return 0;
        }

        return $slot->booked / $slot->capacity;
    }

    /**
     * Get default SLA policy.
     */
    private function getDefaultSlaPolicy(): SlaPolicy
    {
        return SlaPolicy::where('is_active', true)
            ->where('code', 'default')
            ->first() ?? SlaPolicy::create([
                'name' => 'Default SLA',
                'code' => 'default',
                'base_minutes' => 240, // 4 hours
                'night_coef' => 1.2,
                'snow_coef' => 1.5,
                'overload_coef' => 1.3,
            ]);
    }

    /**
     * Get location code from address.
     */
    private function getLocationCode(?object $address): string
    {
        if (! $address) {
            return 'default';
        }

        // Use postal code as location identifier
        return $address->postal_code ?? 'default';
    }

    /**
     * Fetch weather from external API.
     */
    private function fetchWeatherFromApi(string $locationCode): ?array
    {
        // Placeholder - would integrate with weather API
        // For now, return mock data
        return [
            'temperature' => 5.0,
            'humidity' => 80.0,
            'wind_speed' => 15.0,
            'precipitation' => 0.0,
            'condition' => 'clear',
        ];
    }

    /**
     * Generate SLA alerts for dispatch.
     */
    public function generateSlaAlerts(): array
    {
        $atRiskOrders = $this->getOrdersAtRisk();
        $alerts = [];

        foreach ($atRiskOrders as $order) {
            $alert = [
                'type' => 'sla_breach_risk',
                'order_id' => $order['id'],
                'order_number' => $order['order_number'],
                'priority' => $order['priority'],
                'message' => $this->generateAlertMessage($order),
                'recommendations' => $this->generateRecommendations($order),
                'timestamp' => now()->toISOString(),
            ];

            $alerts[] = $alert;
        }

        return $alerts;
    }

    /**
     * Generate alert message.
     */
    private function generateAlertMessage(array $order): string
    {
        $timeToDeadline = $order['time_to_deadline'];

        if ($timeToDeadline < 0) {
            return "Order {$order['order_number']} has breached SLA deadline!";
        } elseif ($timeToDeadline < 15) {
            return "Order {$order['order_number']} is at critical SLA risk ({$timeToDeadline} min remaining)";
        } elseif ($timeToDeadline < 30) {
            return "Order {$order['order_number']} is at high SLA risk ({$timeToDeadline} min remaining)";
        } else {
            return "Order {$order['order_number']} is at medium SLA risk ({$timeToDeadline} min remaining)";
        }
    }

    /**
     * Generate recommendations for order.
     */
    private function generateRecommendations(array $order): array
    {
        $recommendations = [];

        if ($order['time_to_deadline'] < 30) {
            $recommendations[] = 'Assign to nearest available courier';
            $recommendations[] = 'Consider priority routing';
        }

        if ($order['weather_impact'] && $this->isBadWeather($order['weather_impact'])) {
            $recommendations[] = 'Weather conditions may delay delivery';
            $recommendations[] = 'Consider rescheduling if possible';
        }

        if (! $order['assigned_courier']) {
            $recommendations[] = 'Order needs courier assignment';
        }

        return $recommendations;
    }

    /**
     * Update SLA policies based on performance.
     */
    public function updateSlaPolicies(): void
    {
        $policies = SlaPolicy::where('is_active', true)->get();

        foreach ($policies as $policy) {
            $performance = $this->calculatePolicyPerformance($policy);

            // Adjust coefficients based on performance
            if ($performance['breach_rate'] > 0.1) { // 10% breach rate
                $policy->update([
                    'base_minutes' => (int) ($policy->base_minutes * 1.1), // Increase by 10%
                ]);
            }
        }
    }

    /**
    // Start of Selection
    /**
     * Calculate policy performance metrics.
     */
    private function calculatePolicyPerformance(SlaPolicy $policy): array
    {
        $orders = Order::where('sla_policy_id', $policy->id)
            ->where('created_at', '>=', now()->subDays(30))
            ->get();

        $totalOrders = $orders->count();
        $breachedOrders = $orders->where('sla_deadline', '<', now())->count();

        return [
            'total_orders' => $totalOrders,
            'breached_orders' => $breachedOrders,
            'breach_rate' => $totalOrders > 0 ? $breachedOrders / $totalOrders : 0,
        ];
    }
}
