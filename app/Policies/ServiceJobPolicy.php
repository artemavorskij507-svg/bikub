<?php

namespace App\Policies;

use App\Models\Operations\ServiceJob;
use App\Models\User;

class ServiceJobPolicy
{
    private function allowed(User $user, string $permission): bool
    {
        return $user->hasRole('admin')
            || $user->hasPermission($permission)
            || $user->can($permission);
    }

    private function sameOrganization(User $user, ServiceJob $job): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        $userOrganizationId = $user->organization_id ?? $user->default_org_id ?? null;
        $jobOrganizationId = $job->organization_id ?? null;

        if ($userOrganizationId === null || $jobOrganizationId === null) {
            return false;
        }

        return (string) $userOrganizationId === (string) $jobOrganizationId;
    }

    public function viewAny(User $user): bool
    {
        return $this->allowed($user, 'ops.service_jobs.viewAny');
    }

    public function view(User $user, ServiceJob $job): bool
    {
        return $this->allowed($user, 'ops.service_jobs.view')
            && $this->sameOrganization($user, $job);
    }

    public function dispatch(User $user, ServiceJob $job): bool
    {
        return $this->allowed($user, 'ops.service_jobs.dispatch')
            && $this->sameOrganization($user, $job);
    }

    public function update(User $user, ServiceJob $job): bool
    {
        return $this->allowed($user, 'ops.service_jobs.update')
            && $this->sameOrganization($user, $job);
    }
}
