<?php

namespace App\Policies;

use App\Domain\AgentOS\Models\AgentRun;
use App\Models\User;

class AgentRunPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isAdmin($user) || $this->hasOrgContext($user);
    }

    public function view(User $user, AgentRun $run): bool
    {
        return $this->isAdmin($user) || $this->ownsRun($user, $run);
    }

    public function create(User $user): bool
    {
        return $this->isAdmin($user) || $this->hasOrgContext($user);
    }

    public function updateStatus(User $user, AgentRun $run): bool
    {
        return $this->isAdmin($user) || $this->ownsRun($user, $run);
    }

    protected function ownsRun(User $user, AgentRun $run): bool
    {
        $userOrg = $this->userOrganizationId($user);
        $userTenant = $this->userTenantId($user);
        $runOrg = (string) ($run->organization_id ?? '');
        $runTenant = $run->tenant_id;

        if ($userOrg === '' || $runOrg === '' || $userOrg !== $runOrg) {
            return false;
        }

        if ($userTenant === null && $runTenant === null) {
            return true;
        }

        return (string) $userTenant === (string) $runTenant;
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

