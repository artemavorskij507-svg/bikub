<?php

namespace App\Http\Controllers\Api\Ops\Concerns;

use App\Domain\Ops\Actions\ResolveOrganizationScopeAction;

trait ResolvesOpsScope
{
    protected function resolveOrganizationScope(string|int|null $resourceOrganizationId = null): string
    {
        return app(ResolveOrganizationScopeAction::class)->execute(auth()->user(), $resourceOrganizationId);
    }
}

