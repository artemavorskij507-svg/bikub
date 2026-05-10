<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    // fix: grant full access for admin/operator/dispatcher during setup to avoid 403 in editor
    public function before(?User $user, string $ability): ?bool
    {
        if (! $user) {
            return false;
        }
        if (method_exists($user, 'hasAnyRole') && $user->hasAnyRole(['admin', 'operator', 'dispatcher'])) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin') ||
               $user->hasRole('operator') ||
               $user->hasRole('customer');
    }

    public function view(User $user, Order $order): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('customer')) {
            return $order->user_id === $user->id;
        }

        return $user->hasRole('operator');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin') || $user->hasRole('customer');
    }

    public function update(User $user, Order $order): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('customer')) {
            return $order->user_id === $user->id &&
                   in_array($order->status, ['pending', 'confirmed']);
        }

        return $user->hasRole('operator');
    }

    public function delete(User $user, Order $order): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('customer')) {
            return $order->user_id === $user->id &&
                   $order->status === 'pending';
        }

        return false;
    }

    public function cancel(User $user, Order $order): bool
    {
        if ($user->hasRole('admin') || $user->hasRole('operator')) {
            return true;
        }

        if ($user->hasRole('customer')) {
            return $order->user_id === $user->id &&
                   ! in_array($order->status, ['completed', 'canceled']);
        }

        return false;
    }
}
