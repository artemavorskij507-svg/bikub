<?php

namespace App\Domain\Ops\Actions;

use Illuminate\Contracts\Auth\Authenticatable;
use RuntimeException;

class ResolveOrganizationScopeAction
{
    public function execute(?Authenticatable $user, string|int|null $resourceOrganizationId = null): string
    {
        $organizationId = $this->tryResolve($user, $resourceOrganizationId);

        if ($organizationId === null) {
            throw new RuntimeException('Authenticated user has no organization scope.');
        }

        return $organizationId;
    }

    public function tryResolve(?Authenticatable $user, string|int|null $resourceOrganizationId = null): ?string
    {
        $userOrganizationId = $user?->organization_id ?? null;
        if ($userOrganizationId !== null && $userOrganizationId !== '') {
            return (string) $userOrganizationId;
        }

        $defaultOrganizationId = $user?->default_org_id ?? null;
        if ($defaultOrganizationId !== null && $defaultOrganizationId !== '') {
            return (string) $defaultOrganizationId;
        }

        if ($resourceOrganizationId !== null && $resourceOrganizationId !== '') {
            return (string) $resourceOrganizationId;
        }

        return null;
    }
}
