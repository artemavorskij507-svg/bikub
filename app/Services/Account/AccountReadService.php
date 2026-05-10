<?php

namespace App\Services\Account;

use App\Models\ClientProfile;
use App\Models\Order;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class AccountReadService
{
    public function getRecentOrdersForUser(User $user, int $limit = 5): Collection
    {
        return $this->getRecentOrdersForUserAndClient($user, null, $limit);
    }

    public function getRecentOrdersForUserAndClient(
        User $user,
        ?ClientProfile $client,
        int $limit = 5
    ): Collection {
        return $this->buildOrdersQuery($user, $client)
            ->limit($limit)
            ->get();
    }

    public function getPaginatedOrdersForUser(
        User $user,
        ?string $serviceType = null,
        ?string $status = null,
        int $perPage = 15
    ): LengthAwarePaginator {
        return $this->getPaginatedOrdersForUserAndClient(
            $user,
            null,
            $serviceType,
            $status,
            $perPage
        );
    }

    public function getPaginatedOrdersForUserAndClient(
        User $user,
        ?ClientProfile $client,
        ?string $serviceType = null,
        ?string $status = null,
        int $perPage = 15
    ): LengthAwarePaginator {
        $query = $this->buildOrdersQuery($user, $client);

        if ($serviceType) {
            $query->where('service_type', $serviceType);
        }

        if ($status) {
            $query->where('status', $status);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function userCanAccessOrder(User $user, Order $order): bool
    {
        return $order->user_id === $user->id;
    }

    public function getOrderKpiForUser(User $user): array
    {
        $baseQuery = Order::query()->where('user_id', $user->id);

        $active = (clone $baseQuery)
            ->whereIn('status', ['pending', 'confirmed', 'assigned', 'in_progress'])
            ->count();

        $completed = (clone $baseQuery)
            ->where('status', 'completed')
            ->where('completed_at', '>=', now()->copy()->subMonth())
            ->count();

        $total = (clone $baseQuery)->count();

        return [
            'active' => $active,
            'completed' => $completed,
            'total' => $total,
        ];
    }

    protected function buildOrdersQuery(User $user, ?ClientProfile $client = null): Builder
    {
        $query = Order::query()
            ->where('user_id', $user->id)
            ->with($this->eagerRelations())
            ->latest('created_at');

        if ($client && Schema::hasTable('order_care_contexts')) {
            $query->whereHas('careContext', function ($q) use ($client) {
                $q->where('client_profile_id', $client->id);
            });
        }

        return $query;
    }

    protected function eagerRelations(): array
    {
        $relations = [];

        if (Schema::hasColumn('orders', 'parent_order_id')) {
            $relations[] = 'subOrders';
            $relations[] = 'parentOrder';
        }

        if (Schema::hasTable('care_order_details')) {
            $relations[] = 'careDetails';
            $relations[] = 'careDetails.careService';
            $relations[] = 'careDetails.assignedHelper.user';
        }

        if (Schema::hasTable('order_care_contexts')) {
            $relations[] = 'careContext';
        }

        if (Schema::hasTable('disposal_order_details')) {
            $relations[] = 'disposalDetails';
        }

        if (Schema::hasTable('roadside_assistance_details')) {
            $relations[] = 'roadsideDetails';
        }

        if (Schema::hasTable('roadside_emergencies')) {
            $relations[] = 'roadsideEmergency';
        }

        if (Schema::hasTable('addresses')) {
            $relations[] = 'address';
        }

        if (Schema::hasTable('geo_zones')) {
            $relations[] = 'geoZone';
        }

        return $relations;
    }
}
