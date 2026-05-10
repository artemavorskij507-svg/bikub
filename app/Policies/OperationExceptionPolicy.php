<?php

namespace App\Policies;

use App\Domain\Exceptions\Models\OperationException;
use App\Models\User;

class OperationExceptionPolicy
{
    private function allowed(User $user, string $permission): bool
    {
        return $user->hasRole('admin')
            || $user->hasPermission($permission)
            || $user->can($permission);
    }

    private function sameOrganization(User $user, OperationException $exception): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        $userOrganizationId = $user->organization_id ?? $user->default_org_id ?? null;
        $exceptionOrganizationId = $exception->organization_id ?? null;

        if ($userOrganizationId === null || $exceptionOrganizationId === null) {
            return false;
        }

        return (string) $userOrganizationId === (string) $exceptionOrganizationId;
    }

    public function viewAny(User $user): bool
    {
        return $this->allowed($user, 'ops.exceptions.viewAny');
    }

    public function update(User $user, OperationException $exception): bool
    {
        return $this->allowed($user, 'ops.exceptions.update')
            && $this->sameOrganization($user, $exception);
    }

    public function resolve(User $user, OperationException $exception): bool
    {
        return $this->allowed($user, 'ops.exceptions.resolve')
            && $this->sameOrganization($user, $exception);
    }
}
