<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WorkSpecification;

class WorkSpecificationPolicy
{
    /**
     * Общий хелпер: кто может управлять WorkSpecification.
     * Правим роли под твою проектную модель.
     */
    protected function canManage(User $user): bool
    {
        // Если используешь spatie/laravel-permission — этот вызов уже есть в проекте
        if (method_exists($user, 'hasAnyRole')) {
            return $user->hasAnyRole(['admin', 'operator']);
        }

        // Фолбэк: пускаем только если есть поле role = 'admin'
        return in_array($user->role ?? null, ['admin', 'operator'], true);
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $this->canManage($user);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, WorkSpecification $workSpecification): bool
    {
        return $this->canManage($user);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $this->canManage($user);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, WorkSpecification $workSpecification): bool
    {
        return $this->canManage($user);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, WorkSpecification $workSpecification): bool
    {
        return $this->canManage($user);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, WorkSpecification $workSpecification): bool
    {
        return $this->canManage($user);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, WorkSpecification $workSpecification): bool
    {
        return $this->canManage($user);
    }
}
