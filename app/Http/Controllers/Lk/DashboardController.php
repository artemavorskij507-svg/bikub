<?php

namespace App\Http\Controllers\Lk;

use App\Enums\DeliveryTrackingStatus;
use App\Http\Controllers\Controller;
use App\Models\Delivery\DeliveryOrder;
use App\Models\HandymanAssignment;
use App\Models\Order;
use App\Models\RoadsideEmergency;
use App\Models\ScheduleSlot;
use App\Models\Task;
use App\Models\WorkerStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    /**
     * Display the dashboard.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $data = $this->getDashboardData($user);

        return view('lk.dashboard', $data);
    }

    /**
     * API endpoint for refreshing dashboard data (AJAX polling).
     */
    public function refresh(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            // Кешируем данные на 5 секунд для снижения нагрузки
            $cacheKey = "dashboard_data_user_{$user->id}";
            $data = Cache::remember($cacheKey, 5, function () use ($user) {
                try {
                    return $this->getDashboardData($user, true);
                } catch (\Throwable $e) {
                    \Log::error('Error getting dashboard data', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                    throw $e;
                }
            });

            return response()->json([
                'success' => true,
                'data' => $data,
                'timestamp' => now()->toIso8601String(),
            ]);
        } catch (\Throwable $e) {
            \Log::error('Error refreshing dashboard', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Ошибка обновления данных',
                'message' => config('app.debug') ? $e->getMessage() : 'Попробуйте обновить страницу',
            ], 500);
        }
    }

    /**
     * Get dashboard data for user.
     */
    protected function getDashboardData($user, $forApi = false)
    {
        // Загружаем роли один раз
        $user->loadMissing('roles');

        // Определяем роль и загружаем соответствующие данные
        $isCourier = $user->hasRole('courier');
        $isExecutor = $user->hasRole('executor') || $user->hasRole('handyman');

        // Активный заказ/задание в зависимости от роли
        $activeDeliveryOrder = null;
        $activeAssignment = null;

        if ($isCourier) {
            // Для курьера: ищем активный DeliveryOrder
            $activeDeliveryOrder = DeliveryOrder::where('courier_id', $user->id)
                ->whereIn('tracking_status', [
                    DeliveryTrackingStatus::ASSIGNED->value,
                    DeliveryTrackingStatus::PICKED_UP->value,
                    DeliveryTrackingStatus::IN_TRANSIT->value,
                ])
                ->with(['order.address', 'order.user'])
                ->first();
        }

        if ($isExecutor) {
            // Для мастера: ищем активное HandymanAssignment
            $executorProfile = $user->executorProfile;

            // Автоподключение профиля исполнителя для пользователей с соответствующей ролью
            if (! $executorProfile) {
                $canAutoProvision = $user->hasAnyRole([
                    'executor',
                    'courier',
                    'roadside_assist',
                    'eco_executor',
                    'admin',
                    'operator',
                ]);

                if ($canAutoProvision) {
                    $executorProfile = \App\Models\Moving\ExecutorProfile::create([
                        'user_id' => $user->id,
                        'vehicle_type' => 'van',
                        'skills' => ['delivery', 'handyman', 'roadside'],
                        'max_volume' => 12,
                        'max_weight' => 800,
                        'insurance_limit' => 100000,
                        'rating' => 5.0,
                        'completed_orders_count' => 0,
                        'is_active' => true,
                        'last_active_at' => now(),
                        'metadata' => ['auto_provisioned' => true],
                    ]);
                }
            }

            if ($executorProfile) {
                $activeAssignment = HandymanAssignment::where('executor_profile_id', $executorProfile->id)
                    ->whereIn('status', ['assigned', 'in_progress', 'started'])
                    ->with(['order.address', 'order.user', 'repairProject'])
                    ->first();
            }
        }

        // Статистика за сегодня (оптимизировано одним запросом)
        $todayStart = now()->startOfDay();
        $todayStats = Order::where('assigned_to', $user->id)
            ->where('status', 'completed')
            ->where('completed_at', '>=', $todayStart)
            ->selectRaw('SUM(total_amount) as earnings, COUNT(*) as completed')
            ->first();

        $todayEarnings = (float) ($todayStats->earnings ?? 0);
        $todayCompleted = (int) ($todayStats->completed ?? 0);

        // Статистика для графика (последние 7 дней)
        $chartData = Order::where('assigned_to', $user->id)
            ->where('status', 'completed')
            ->where('completed_at', '>=', now()->subDays(6)->startOfDay())
            ->selectRaw('DATE(completed_at) as date, SUM(total_amount) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('total', 'date');

        $earningsChart = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $earningsChart[$date] = (float) ($chartData[$date] ?? 0);
        }

        // Последние события
        $recentEvents = [];
        // Если есть таблица уведомлений
        if (\Illuminate\Support\Facades\Schema::hasTable('notifications')) {
            $recentEvents = $user->notifications()
                ->latest()
                ->limit(5)
                ->get()
                ->map(function ($notification) {
                    return [
                        'id' => $notification->id,
                        'title' => $notification->data['title'] ?? 'Уведомление',
                        'message' => $notification->data['message'] ?? $notification->data['body'] ?? 'Новое событие',
                        'type' => $notification->data['type'] ?? 'info',
                        'created_at' => $notification->created_at->diffForHumans(),
                        'is_read' => ! is_null($notification->read_at),
                    ];
                });
        }

        // Статус работника (онлайн/офлайн)
        $workerStatus = null;
        if (Schema::hasTable('worker_statuses')) {
            $workerStatus = WorkerStatus::firstOrCreate(
                ['user_id' => $user->id],
                ['is_online' => false, 'updated_at' => now()]
            );
        }

        // Дорожные задания (для пользователей с соответствующими ролями)
        $roadsideJobs = null;
        $roadsideActiveOrders = 0;
        $roadsideCompletedToday = 0;
        $roadsideEarned = 0.0;

        if ($user->hasAnyRole(['roadside_assist', 'executor', 'eco_executor', 'tow_operator'])) {
            try {
                $roadsideJobs = RoadsideEmergency::query()
                    ->where(function ($q) use ($user) {
                        $q->whereHas('order', function ($oq) use ($user) {
                            $oq->where('assigned_to', $user->id);
                        })
                            ->orWhereHas('helper', function ($hq) use ($user) {
                                $hq->where('user_id', $user->id);
                            });
                    })
                    ->active()
                    ->with(['order', 'customer'])
                    ->orderBy('created_at')
                    ->limit(3)
                    ->get();

                // Roadside статистика для виджета
                $roadsideServiceTypes = [
                    'roadside_assistance',
                    'vehicle_inspection',
                    'vehicle_transport',
                ];

                $roadsideActiveOrders = Order::where('assigned_to', $user->id)
                    ->where(function ($q) use ($roadsideServiceTypes) {
                        $q->whereHas('roadsideEmergency')
                            ->orWhereHas('vehicleInspection')
                            ->orWhereHas('orderItems.serviceType', function ($sq) use ($roadsideServiceTypes) {
                                $sq->whereIn('code', $roadsideServiceTypes)
                                    ->orWhereIn('category', $roadsideServiceTypes);
                            });
                    })
                    ->whereIn('status', ['assigned', 'in_progress'])
                    ->count();

                $roadsideCompletedToday = Order::where('assigned_to', $user->id)
                    ->where(function ($q) use ($roadsideServiceTypes) {
                        $q->whereHas('roadsideEmergency')
                            ->orWhereHas('vehicleInspection')
                            ->orWhereHas('orderItems.serviceType', function ($sq) use ($roadsideServiceTypes) {
                                $sq->whereIn('code', $roadsideServiceTypes)
                                    ->orWhereIn('category', $roadsideServiceTypes);
                            });
                    })
                    ->where('status', 'completed')
                    ->whereDate('completed_at', today())
                    ->count();

                // Заработано по Roadside через Task
                $roadsideEarned = Task::join('orders', 'tasks.order_id', '=', 'orders.id')
                    ->where(function ($q) use ($user) {
                        $q->where('orders.assigned_to', $user->id)
                            ->orWhere(function ($sq) use ($user) {
                                $sq->where('tasks.type', 'roadside_job')
                                    ->whereRaw("JSON_EXTRACT(tasks.meta, '$.executor_user_id') = ?", [$user->id]);
                            });
                    })
                    ->where('tasks.type', 'roadside_job')
                    ->where('tasks.status', 'completed')
                    ->whereNotNull('tasks.payout_amount')
                    ->sum('tasks.payout_amount') ?? 0.0;
            } catch (\Throwable $e) {
                // временно проглатываем, можно залогировать
                // \Log::warning('Roadside stats error', ['error' => $e->getMessage()]);
                $roadsideJobs = collect();
            }
        }

        // Следующая смена (если есть Employee)
        $nextShift = null;
        $employee = $user->employee;
        $hasScheduleSchema = Schema::hasTable('schedule_slots')
            && Schema::hasTable('schedule_slot_employees')
            && Schema::hasTable('employees');

        if ($employee && $hasScheduleSchema) {
            $nextShift = ScheduleSlot::whereHas('employees', function ($query) use ($employee) {
                $query->where('employees.id', $employee->id);
            })
                ->where('start_at', '>=', now())
                ->with(['zone', 'serviceType'])
                ->orderBy('start_at', 'asc')
                ->first();
        }
        $activeDeliveryOrderData = null;
        if ($activeDeliveryOrder) {
            $activeDeliveryOrderData = [
                'id' => $activeDeliveryOrder->id,
                'order_id' => $activeDeliveryOrder->order_id,
                'order_number' => $activeDeliveryOrder->order->order_number ?? null,
                'tracking_status' => $activeDeliveryOrder->tracking_status->value ?? null,
                'address' => $activeDeliveryOrder->order->address->address_line_1 ?? null,
                'city' => $activeDeliveryOrder->order->address->city ?? null,
                'estimated_distance_km' => $activeDeliveryOrder->estimated_distance_km,
                'eta' => $activeDeliveryOrder->eta?->toIso8601String(),
            ];
        }

        $activeAssignmentData = null;
        if ($activeAssignment) {
            $activeAssignmentData = [
                'id' => $activeAssignment->id,
                'order_id' => $activeAssignment->order_id,
                'order_number' => $activeAssignment->order->order_number ?? null,
                'status' => $activeAssignment->status,
                'address' => $activeAssignment->order->address->address_line_1 ?? null,
                'city' => $activeAssignment->order->address->city ?? null,
                'planned_start_at' => $activeAssignment->planned_start_at?->toIso8601String(),
            ];
        }

        $assignedOrders = Order::query()
            ->where('assigned_to', $user->id)
            ->whereIn('status', ['assigned', 'worker_accepted', 'worker_en_route', 'at_pickup', 'picked_up', 'in_progress', 'arrived'])
            ->orderByDesc('created_at')
            ->limit(6)
            ->get(['id', 'order_number', 'status', 'payment_status', 'metadata', 'scheduled_at']);

        $availableOrders = collect();
        if ($user->hasAnyRole(['worker', 'courier', 'executor', 'handyman'])) {
            $availableOrders = Order::query()
                ->whereNull('assigned_to')
                ->whereIn('status', ['waiting_dispatch', 'assigned'])
                ->orderByDesc('created_at')
                ->limit(6)
                ->get(['id', 'order_number', 'status', 'payment_status', 'metadata', 'scheduled_at']);
        }

        $data = [
            'user' => $user,
            'isCourier' => $isCourier,
            'isExecutor' => $isExecutor,
            'activeDeliveryOrder' => $activeDeliveryOrder,
            'activeAssignment' => $activeAssignment,
            'activeDeliveryOrderData' => $activeDeliveryOrderData,
            'activeAssignmentData' => $activeAssignmentData,
            'todayEarnings' => $todayEarnings,
            'todayCompleted' => $todayCompleted,
            'workerStatus' => $workerStatus,
            'roadsideJobs' => $roadsideJobs ?? null,
            'roadsideActiveOrders' => $roadsideActiveOrders ?? 0,
            'roadsideCompletedToday' => $roadsideCompletedToday ?? 0,
            'roadsideEarned' => $roadsideEarned ?? 0,
            'nextShift' => $nextShift ?? null,
            'earningsChart' => $earningsChart,
            'recentEvents' => $recentEvents,
            'assignedOrders' => $assignedOrders,
            'availableOrders' => $availableOrders,
        ];

        // Для API возвращаем только необходимые данные
        if ($forApi) {
            return [
                'isOnline' => $workerStatus->is_online,
                'todayEarnings' => $todayEarnings,
                'todayCompleted' => $todayCompleted,
                'hasActiveOrder' => $activeDeliveryOrder !== null || $activeAssignment !== null,
                'activeDeliveryOrder' => $activeDeliveryOrder ? [
                    'id' => $activeDeliveryOrder->id,
                    'order_id' => $activeDeliveryOrder->order_id,
                    'order_number' => $activeDeliveryOrder->order->order_number ?? null,
                    'tracking_status' => $activeDeliveryOrder->tracking_status->value ?? null,
                    'address' => $activeDeliveryOrder->order->address->address_line_1 ?? null,
                    'city' => $activeDeliveryOrder->order->address->city ?? null,
                    'estimated_distance_km' => $activeDeliveryOrder->estimated_distance_km,
                    'eta' => $activeDeliveryOrder->eta?->toIso8601String(),
                ] : null,
                'activeAssignment' => $activeAssignment ? [
                    'id' => $activeAssignment->id,
                    'order_id' => $activeAssignment->order_id,
                    'order_number' => $activeAssignment->order->order_number ?? null,
                    'status' => $activeAssignment->status,
                    'address' => $activeAssignment->order->address->address_line_1 ?? null,
                    'city' => $activeAssignment->order->address->city ?? null,
                    'planned_start_at' => $activeAssignment->planned_start_at?->toIso8601String(),
                ] : null,
                'earningsChart' => $earningsChart, // Обновляем график если нужно
            ];
        }

        return $data;
    }
}

