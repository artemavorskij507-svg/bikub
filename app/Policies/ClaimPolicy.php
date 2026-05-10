<?php

namespace App\Policies;

use App\Models\Claim;
use App\Models\User;

class ClaimPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'dispatcher', 'project_manager', 'support']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Claim $claim): bool
    {
        // Клиент видит только свои претензии
        if ($user->id === $claim->user_id) {
            return true;
        }

        // ADMIN всё видит
        if ($user->hasRole('admin')) {
            return true;
        }

        // DISPATCHER — все претензии по доставке/handyman (по category/order service)
        if ($user->hasRole('dispatcher')) {
            return true; // при желании можно сузить по типу
        }

        // PROJECT_MANAGER — только по ремонтам
        if ($user->hasRole('project_manager') && $claim->repair_project_id) {
            return true;
        }

        // SUPPORT — может видеть, но без чувствительных деталей в UI
        if ($user->hasRole('support')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Любой авторизованный пользователь может создать претензию
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Claim $claim): bool
    {
        // Только OPS-ролями
        return $user->hasAnyRole(['admin', 'dispatcher', 'project_manager', 'support']);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Claim $claim): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Claim $claim): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Claim $claim): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can assign handler to the claim.
     */
    public function assignHandler(User $user, Claim $claim): bool
    {
        return $user->hasAnyRole(['admin', 'dispatcher', 'project_manager']);
    }
}
