<?php

namespace App\Policies;

use App\Domain\AgentOS\Models\AgentStep;
use App\Models\User;

class AgentStepPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isAdmin($user) || $this->hasOrgContext($user);
    }

    public function view(User $user, AgentStep $step): bool
    {
        return $this->isAdmin($user) || $this->ownsStep($user, $step);
    }

    public function updateStatus(User $user, AgentStep $step): bool
    {
        return $this->isAdmin($user) || $this->ownsStep($user, $step);
    }

    public function heartbeat(User $user, AgentStep $step): bool
    {
        return $this->isAdmin($user) || $this->ownsStep($user, $step);
    }

    protected function ownsStep(User $user, AgentStep $step): bool
    {
        $userOrg = $this->userOrganizationId($user);
        $userTenant = $this->userTenantId($user);
        $stepOrg = (string) ($step->organization_id ?? '');
        $stepTenant = $step->tenant_id;

        if ($userOrg === '' || $stepOrg === '' || $userOrg !== $stepOrg) {
            return false;
        }

        if ($userTenant === null && $stepTenant === null) {
            return true;
        }

        return (string) $userTenant === (string) $stepTenant;
    }

    protected function hasOrgContext(User $user): bool
    {
        return $this->userOrganizationId($user) !== '';
    }

    protected function userOrganizationId(User $user): string
    {
        return (string) ($user->organization_id ?? $user->default_org_id ?? '');
    }

    protected function userTenantId(User $user): ?int
    {
        $value = $user->tenant_id ?? null;

        return is_numeric($value) ? (int) $value : null;
    }

    protected function isAdmin(User $user): bool
    {
        if (! method_exists($user, 'hasRole')) {
            return false;
        }

        try {
            return $user->hasRole('admin') || $user->hasRole('super_admin');
        } catch (\Throwable) {
            return false;
        }
    }
}

