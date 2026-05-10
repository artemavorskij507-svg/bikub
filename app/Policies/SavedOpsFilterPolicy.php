<?php

namespace App\Policies;

use App\Domain\Ops\Models\SavedOpsFilter;
use App\Models\User;

class SavedOpsFilterPolicy
{
    private function sameOrganization(User $user, SavedOpsFilter $filter): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        $userOrganizationId = $user->organization_id ?? $user->default_org_id ?? null;
        $filterOrganizationId = $filter->organization_id ?? null;

        if ($userOrganizationId === null || $filterOrganizationId === null) {
            return false;
        }

        return (string) $userOrganizationId === (string) $filterOrganizationId;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin')
            || $user->hasPermission('ops.service_jobs.viewAny')
            || $user->can('ops.service_jobs.viewAny');
    }

    public function delete(User $user, SavedOpsFilter $filter): bool
    {
        if (! $this->sameOrganization($user, $filter)) {
            return false;
        }

        if ($user->hasRole('admin')) {
            return true;
        }

        if ((int) $filter->user_id === (int) $user->id) {
            return true;
        }

        return $user->hasPermission('ops.rules.update')
            || $user->can('ops.rules.update');
    }
}
