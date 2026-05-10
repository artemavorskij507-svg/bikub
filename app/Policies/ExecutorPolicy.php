<?php

namespace App\Policies;

use App\Models\Operations\Executor;
use App\Models\User;

class ExecutorPolicy
{
    private function allowed(User $user, string $permission): bool
    {
        return $user->hasRole('admin')
            || $user->hasPermission($permission)
            || $user->can($permission);
    }

    private function sameOrganization(User $user, Executor $executor): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        $userOrganizationId = $user->organization_id ?? $user->default_org_id ?? null;
        $executorOrganizationId = $executor->organization_id ?? null;

        if ($userOrganizationId === null || $executorOrganizationId === null) {
            return false;
        }

        return (string) $userOrganizationId === (string) $executorOrganizationId;
    }

    public function viewAny(User $user): bool
    {
        return $this->allowed($user, 'ops.executors.viewAny');
    }

    public function view(User $user, Executor $executor): bool
    {
        return $this->allowed($user, 'ops.executors.view')
            && $this->sameOrganization($user, $executor);
    }

    public function updateAvailability(User $user, Executor $executor): bool
    {
        return $this->allowed($user, 'ops.executors.updateAvailability')
            && $this->sameOrganization($user, $executor);
    }

    public function locationPing(User $user, Executor $executor): bool
    {
        return (
            $user->id === $executor->user_id
            || $this->allowed($user, 'ops.executors.locationPing')
        ) && $this->sameOrganization($user, $executor);
    }
}
