<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Route;
use App\Models\ScheduleSlot;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DispatchController extends Controller
{
    /**
     * Get dispatch state snapshot.
     */
    public function getState(Request $request)
    {
        try {
            $filters = $request->only(['module', 'zone', 'slot', 'sla_risk', 'status']);

            // Cache dispatch state for 30 seconds
            $cacheKey = 'dispatch_state_'.md5(serialize($filters));

            $state = Cache::remember($cacheKey, 30, function () use ($filters) {
                return $this->buildDispatchState($filters);
            });

            return response()->json([
                'success' => true,
                'data' => $state ?? [],
                'timestamp' => now()->toISOString(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Dispatch state error: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'data' => [],
                'error' => 'Failed to load dispatch state',
                'timestamp' => now()->toISOString(),
            ], 500);
        }
    }

    /**
     * Build dispatch state data.
     */
    private function buildDispatchState(array $filters = [])
    {
        try {
            $query = Order::with(['user', 'orderItems.serviceType', 'scheduleSlot', 'tasks.assignee']);

            // Apply filters
            if (isset($filters['module'])) {
                $query->whereHas('orderItems.serviceType.serviceCategory', function ($q) use ($filters) {
                    $q->where('code', $filters['module']);
                });
            }

            if (isset($filters['zone'])) {
                $query->whereJsonContains('metadata->geo_zone', $filters['zone']);
            }

            if (isset($filters['slot'])) {
                $query->whereHas('scheduleSlot', function ($q) use ($filters) {
                    $q->where('code', $filters['slot']);
                });
            }

            if (isset($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            $orders = $query->orderBy('created_at', 'desc')->limit(100)->get();

            // Transform orders for frontend
            $ordersData = $orders->map(function ($order) {
                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'status' => $order->status,
                    'priority' => $order->priority,
                    'location' => $order->location,
                    'scheduled_at' => $order->scheduled_at,
                    'schedule_slot' => $order->scheduleSlot ? [
                        'code' => $order->scheduleSlot->code,
                        'name' => $order->scheduleSlot->name,
                        'capacity' => $order->scheduleSlot->capacity,
                        'booked' => $order->scheduleSlot->booked,
                        'is_overbooked' => $order->scheduleSlot->isOverbooked(),
                    ] : null,
                    'customer' => [
                        'name' => $order->user->name,
                        'phone' => $order->user->phone,
                    ],
                    'services' => $order->orderItems->map(function ($item) {
                        return [
                            'name' => $item->serviceType->name,
                            'category' => $item->serviceType->serviceCategory->name ?? 'Unknown',
                        ];
                    }),
                    'tasks' => $order->tasks->map(function ($task) {
                        return [
                            'id' => $task->id,
                            'status' => $task->status,
                            'assignee' => $task->assignee ? [
                                'name' => $task->assignee->name,
                                'id' => $task->assignee->id,
                            ] : null,
                            'checklist_completion' => $task->getChecklistCompletionPercentage(),
                            'eta' => $task->meta['eta'] ?? null,
                        ];
                    }),
                    'sla_risk' => $this->calculateSlaRisk($order),
                    'created_at' => $order->created_at,
                ];
            });

            // Get active routes
            $routes = Route::with(['routeStops.order'])
                ->where('date', '>=', now()->toDateString())
                ->orderBy('date')
                ->get()
                ->map(function ($route) {
                    return [
                        'id' => $route->id,
                        'date' => $route->date,
                        'vehicle_id' => $route->vehicle_id,
                        'stops_count' => $route->routeStops->count(),
                        'total_eta' => $route->total_eta,
                        'efficiency' => $route->getEfficiencyMetrics(),
                    ];
                });

            // Get schedule slots with capacity info
            $slots = ScheduleSlot::active()->get()->map(function ($slot) {
                return [
                    'id' => $slot->id,
                    'code' => $slot->code,
                    'name' => $slot->name,
                    'from' => $slot->from,
                    'to' => $slot->to,
                    'capacity' => $slot->capacity,
                    'booked' => $slot->booked,
                    'available' => $slot->getAvailableCapacity(),
                    'is_overbooked' => $slot->isOverbooked(),
                    'overbooking_percentage' => $slot->getOverbookingPercentage(),
                ];
            });

            // Get active couriers
            $couriers = User::whereHas('roles', function ($query) {
                $query->where('name', 'courier');
            })->where('is_active', true)->get()->map(function ($courier) {
                $activeTasks = Task::where('assignee_id', $courier->id)
                    ->whereIn('status', ['assigned', 'enroute'])
                    ->count();

                return [
                    'id' => $courier->id,
                    'name' => $courier->name,
                    'active_tasks' => $activeTasks,
                    'last_seen' => $courier->last_login_at,
                ];
            });

            return [
                'orders' => $ordersData,
                'routes' => $routes,
                'slots' => $slots,
                'couriers' => $couriers,
                'summary' => [
                    'total_orders' => $orders->count(),
                    'pending_orders' => $orders->where('status', 'pending')->count(),
                    'in_progress_orders' => $orders->where('status', 'in_progress')->count(),
                    'sla_at_risk' => $ordersData->where('sla_risk', 'high')->count(),
                    'overbooked_slots' => $slots->where('is_overbooked', true)->count(),
                    'active_couriers' => $couriers->where('active_tasks', '>', 0)->count(),
                ],
            ];
        } catch (\Exception $e) {
            \Log::error('Build dispatch state error: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'orders' => [],
                'routes' => [],
                'slots' => [],
                'couriers' => [],
                'summary' => [
                    'total_orders' => 0,
                    'pending_orders' => 0,
                    'in_progress_orders' => 0,
                    'sla_at_risk' => 0,
                    'overbooked_slots' => 0,
                    'active_couriers' => 0,
                ],
            ];
        }
    }

    /**
     * Calculate SLA risk for an order.
     */
    private function calculateSlaRisk(Order $order): string
    {
        if (! $order->scheduled_at) {
            return 'medium';
        }

        try {
            // Если scheduled_at уже является Carbon, используем его напрямую
            if ($order->scheduled_at instanceof \Carbon\Carbon) {
                $scheduledTime = $order->scheduled_at;
            } else {
                $scheduledTime = Carbon::parse($order->scheduled_at);
            }
        } catch (\Exception $e) {
            \Log::warning('Failed to parse scheduled_at for SLA calculation', [
                'order_id' => $order->id,
                'scheduled_at' => $order->scheduled_at,
                'error' => $e->getMessage(),
            ]);

            return 'medium';
        }

        $now = now();
        $timeDiff = $now->diffInMinutes($scheduledTime, false);

        // If order is overdue
        if ($timeDiff < 0) {
            return 'critical';
        }

        // If order is within 30 minutes of scheduled time
        if ($timeDiff <= 30) {
            return 'high';
        }

        // If order is within 1 hour of scheduled time
        if ($timeDiff <= 60) {
            return 'medium';
        }

        return 'low';
    }

    /**
     * Update order status.
     */
    public function updateOrderStatus(Request $request, string $id)
    {
        $request->validate([
            'status' => 'required|in:pending,confirmed,in_progress,completed,cancelled',
            'notes' => 'nullable|string',
        ]);

        $order = Order::findOrFail($id);
        $oldStatus = $order->status;

        $order->update([
            'status' => $request->status,
            'notes' => $request->notes ? ($order->notes."\n".$request->notes) : $order->notes,
        ]);

        // Update timestamps
        if ($request->status === 'in_progress' && ! $order->started_at) {
            $order->update(['started_at' => now()]);
        }

        if ($request->status === 'completed' && ! $order->completed_at) {
            $order->update(['completed_at' => now()]);
        }

        // Broadcast update via WebSocket (placeholder)
        $this->broadcastOrderUpdate($order, $oldStatus);

        return response()->json([
            'success' => true,
            'data' => $order->fresh(['user', 'orderItems.serviceType', 'scheduleSlot', 'tasks.assignee']),
            'message' => 'Order status updated successfully',
        ]);
    }

    /**
     * Assign task to courier.
     */
    public function assignTask(Request $request, string $taskId)
    {
        $request->validate([
            'assignee_id' => 'required|exists:users,id',
        ]);

        $task = Task::findOrFail($taskId);

        // Check if courier has the required role
        $courier = User::findOrFail($request->assignee_id);
        if (! $courier->hasRole('courier')) {
            return response()->json([
                'success' => false,
                'message' => 'User is not a courier',
            ], 400);
        }

        $task->update([
            'assignee_id' => $request->assignee_id,
            'assigned_at' => now(),
            'status' => 'assigned',
        ]);

        // Broadcast update via WebSocket (placeholder)
        $this->broadcastTaskUpdate($task);

        return response()->json([
            'success' => true,
            'data' => $task->fresh(['assignee', 'orderItem.order']),
            'message' => 'Task assigned successfully',
        ]);
    }

    /**
     * Get winter protocol settings.
     */
    public function getWinterProtocol()
    {
        $protocol = Cache::get('winter_protocol', [
            'enabled' => false,
            'eta_multiplier' => 1.2,
            'priority_boost' => 1,
        ]);

        return response()->json([
            'success' => true,
            'data' => $protocol,
        ]);
    }

    /**
     * Update winter protocol settings.
     */
    public function updateWinterProtocol(Request $request)
    {
        $request->validate([
            'enabled' => 'boolean',
            'eta_multiplier' => 'numeric|min:1|max:2',
            'priority_boost' => 'integer|min:0|max:3',
        ]);

        $protocol = [
            'enabled' => $request->enabled ?? false,
            'eta_multiplier' => $request->eta_multiplier ?? 1.2,
            'priority_boost' => $request->priority_boost ?? 1,
            'updated_at' => now()->toISOString(),
        ];

        Cache::put('winter_protocol', $protocol, 3600); // Cache for 1 hour

        return response()->json([
            'success' => true,
            'data' => $protocol,
            'message' => 'Winter protocol updated successfully',
        ]);
    }

    /**
     * Broadcast order update (placeholder for WebSocket).
     */
    private function broadcastOrderUpdate(Order $order, string $oldStatus)
    {
        // This would integrate with Laravel WebSockets or Pusher
        // For now, we'll just log the event
        \Log::info('Order status changed', [
            'order_id' => $order->id,
            'old_status' => $oldStatus,
            'new_status' => $order->status,
            'timestamp' => now(),
        ]);
    }

    /**
     * Broadcast task update (placeholder for WebSocket).
     */
    private function broadcastTaskUpdate(Task $task)
    {
        // This would integrate with Laravel WebSockets or Pusher
        \Log::info('Task updated', [
            'task_id' => $task->id,
            'status' => $task->status,
            'assignee_id' => $task->assignee_id,
            'timestamp' => now(),
        ]);
    }
}
