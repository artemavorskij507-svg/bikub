<?php

namespace App\Policies;

use App\Models\Delivery\DeliveryOrder;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DeliveryOrderPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any delivery orders.
     */
    public function viewAny(User $user): bool
    {
        return $this->canManageDeliveries($user);
    }

    /**
     * Determine whether the user can view the delivery order.
     */
    public function view(User $user, DeliveryOrder $deliveryOrder): bool
    {
        return $this->canManageDeliveries($user);
    }

    /**
     * Determine whether the user can create delivery orders.
     */
    public function create(User $user): bool
    {
        return $this->canManageDeliveries($user);
    }

    /**
     * Determine whether the user can update the delivery order.
     */
    public function update(User $user, DeliveryOrder $deliveryOrder): bool
    {
        return $this->canManageDeliveries($user);
    }

    /**
     * Delivery orders are managed automatically — deletion is blocked.
     */
    public function delete(User $user, DeliveryOrder $deliveryOrder): bool
    {
        return false;
    }

    public function restore(User $user, DeliveryOrder $deliveryOrder): bool
    {
        return false;
    }

    public function forceDelete(User $user, DeliveryOrder $deliveryOrder): bool
    {
        return false;
    }

    /**
     * Shared helper for whitelisting delivery managers.
     */
    protected function canManageDeliveries(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'dispatcher']);
    }
}
