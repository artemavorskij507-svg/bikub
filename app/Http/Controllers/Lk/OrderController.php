<?php

namespace App\Http\Controllers\Lk;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $isPrivilegedOps = $user->hasAnyRole(['owner', 'admin', 'dispatcher', 'support', 'operator', 'ops_admin', 'ops_manager', 'ops_rules_admin']);

        $query = Order::where('assigned_to', $user->id)
            ->with($this->orderIndexRelations());

        $typeFilter = $request->input('type');
        if ($typeFilter === 'roadside' && $this->canUseRoadsideFilters()) {
            $query->where(function ($q) {
                $this->applyRoadsideScope($q);
            });
        }

        $statusFilter = $request->input('status', 'all');

        switch ($statusFilter) {
            case 'active':
                $query->whereIn('status', ['assigned', 'worker_accepted', 'worker_en_route', 'at_pickup', 'picked_up', 'in_progress', 'arrived']);
                break;
            case 'completed':
                $query->whereIn('status', ['completed', 'delivered']);
                break;
            case 'upcoming':
                $query->whereIn('status', ['pending', 'confirmed', 'scheduled'])
                    ->where('scheduled_at', '>', now());
                break;
            case 'all':
            default:
                break;
        }

        $query->orderByRaw("
            CASE 
                WHEN status IN ('assigned', 'in_progress') THEN 1
                WHEN status IN ('pending', 'confirmed', 'scheduled') AND scheduled_at > NOW() THEN 2
                ELSE 3
            END
        ")
            ->orderBy('created_at', 'desc');

        $orders = $query->paginate(20);

        $activeCount = Order::where('assigned_to', $user->id)
            ->whereIn('status', ['assigned', 'worker_accepted', 'worker_en_route', 'at_pickup', 'picked_up', 'in_progress', 'arrived'])
            ->count();

        $completedCount = Order::where('assigned_to', $user->id)
            ->whereIn('status', ['completed', 'delivered'])
            ->count();

        $upcomingCount = Order::where('assigned_to', $user->id)
            ->whereIn('status', ['pending', 'confirmed', 'scheduled'])
            ->where('scheduled_at', '>', now())
            ->count();

        $roadsideCountQuery = Order::where('assigned_to', $user->id)
            ->whereIn('status', ['assigned', 'in_progress', 'pending']);

        if ($this->canUseRoadsideFilters()) {
            $roadsideCountQuery->where(function ($q) {
                $this->applyRoadsideScope($q);
            });

            $roadsideCount = $roadsideCountQuery->count();
        } else {
            $roadsideCount = 0;
        }

        $executorAssignments = null;
        if ($user->hasRole('executor') || $user->hasRole('handyman')) {
            $executorProfile = $user->executorProfile;

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
                $executorAssignments = \App\Models\HandymanAssignment::query()
                    ->where('executor_profile_id', $executorProfile->id)
                    ->with(['order', 'order.handymanDetails', 'repairProject'])
                    ->orderByRaw("
                        CASE status
                            WHEN 'proposed' THEN 1
                            WHEN 'accepted' THEN 2
                            WHEN 'in_progress' THEN 3
                            WHEN 'started' THEN 4
                            WHEN 'completed' THEN 5
                            ELSE 6
                        END
                    ")
                    ->orderByDesc('created_at')
                    ->paginate(20);
            }
        }

        return view('lk.orders.index', [
            'user' => $user,
            'orders' => $orders,
            'statusFilter' => $statusFilter,
            'typeFilter' => $typeFilter,
            'isPrivilegedOps' => $isPrivilegedOps,
            'activeCount' => $activeCount,
            'completedCount' => $completedCount,
            'upcomingCount' => $upcomingCount,
            'roadsideCount' => $roadsideCount,
            'executorAssignments' => $executorAssignments,
            'isExecutor' => $user->hasRole('executor') || $user->hasRole('handyman'),
        ]);
    }

    public function show(Request $request, Order $order)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        if ((int) $order->assigned_to !== (int) $user->id
            && ! $user->hasAnyRole(['owner', 'admin', 'dispatcher', 'support', 'operator', 'ops_admin', 'ops_manager', 'ops_rules_admin'])) {
            abort(403, 'У вас нет доступа к этому заказу');
        }

        $order->load($this->orderShowRelations());

        $timeline = [];

        if ($order->created_at) {
            $timeline[] = [
                'event' => 'created',
                'label' => 'Заказ создан',
                'timestamp' => $order->created_at,
                'user' => $order->user->name ?? 'Клиент',
            ];
        }

        if ($order->assigned_to) {
            $assignedAt = $order->metadata['assigned_at'] ?? $order->updated_at;
            $timeline[] = [
                'event' => 'assigned',
                'label' => 'Заказ назначен',
                'timestamp' => is_string($assignedAt) ? \Carbon\Carbon::parse($assignedAt) : $assignedAt,
                'user' => $order->assignedUser->name ?? 'Исполнитель',
            ];
        }

        if ($order->started_at) {
            $timeline[] = [
                'event' => 'started',
                'label' => 'Работа начата',
                'timestamp' => $order->started_at,
                'user' => $order->assignedUser->name ?? 'Исполнитель',
            ];
        }

        if ($order->completed_at) {
            $timeline[] = [
                'event' => 'completed',
                'label' => 'Заказ выполнен',
                'timestamp' => $order->completed_at,
                'user' => $order->assignedUser->name ?? 'Исполнитель',
            ];
        }

        usort($timeline, fn ($a, $b) => $a['timestamp'] <=> $b['timestamp']);

        return view('lk.orders.show', [
            'user' => $user,
            'order' => $order,
            'timeline' => $timeline,
        ]);
    }

    protected function orderIndexRelations(): array
    {
        $relations = ['user', 'assignedUser'];

        if (Schema::hasTable('addresses')) {
            $relations[] = 'address';
        }

        if (Schema::hasTable('order_items') && Schema::hasTable('service_types')) {
            $relations[] = 'orderItems.serviceType';
        }

        return $relations;
    }

    protected function orderShowRelations(): array
    {
        $relations = ['user', 'assignedUser', 'scheduleSlot', 'tasks'];

        if (Schema::hasTable('addresses')) {
            $relations[] = 'address';
        }

        if (Schema::hasTable('order_items') && Schema::hasTable('service_types')) {
            $relations[] = 'orderItems.serviceType';
        }

        if (Schema::hasTable('geo_zones')) {
            $relations[] = 'geoZone';
            $relations[] = 'roadsidePartner.geoZone';
        }

        if (Schema::hasTable('roadside_assistance_details')) {
            $relations[] = 'roadsideDetails.partner';
        }

        if (Schema::hasTable('roadside_emergencies')) {
            $relations[] = 'roadsideEmergency';
        }

        return $relations;
    }

    protected function canUseRoadsideFilters(): bool
    {
        return Schema::hasTable('roadside_assistance_details')
            || Schema::hasTable('roadside_emergencies')
            || Schema::hasTable('vehicle_inspection_requests')
            || (Schema::hasTable('order_items') && Schema::hasTable('service_types'));
    }

    protected function applyRoadsideScope($query): void
    {
        $hasCondition = false;

        if (Schema::hasTable('roadside_assistance_details')) {
            $query->whereHas('roadsideDetails');
            $hasCondition = true;
        }

        if (Schema::hasTable('roadside_emergencies')) {
            $method = $hasCondition ? 'orWhereHas' : 'whereHas';
            $query->{$method}('roadsideEmergency');
            $hasCondition = true;
        }

        if (Schema::hasTable('vehicle_inspection_requests')) {
            $method = $hasCondition ? 'orWhereHas' : 'whereHas';
            $query->{$method}('vehicleInspection');
            $hasCondition = true;
        }

        if (Schema::hasTable('order_items') && Schema::hasTable('service_types')) {
            $method = $hasCondition ? 'orWhereHas' : 'whereHas';
            $query->{$method}('orderItems.serviceType', function ($serviceTypeQuery) {
                $serviceTypeQuery->where(function ($serviceTypeFilter) {
                    $serviceTypeFilter->whereIn('code', ['roadside_assistance', 'vehicle_inspection', 'vehicle_transport'])
                        ->orWhereIn('category', ['roadside_assistance', 'vehicle_inspection', 'vehicle_transport']);
                });
            });
        }
    }
}

