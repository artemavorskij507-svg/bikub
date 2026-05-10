<?php

namespace App\Domain\Ops\Actions;

use App\Domain\Ops\Models\SavedOpsFilter;

class StoreSavedOpsFilterAction
{
    public function execute(
        string|int $organizationId,
        int $userId,
        string $name,
        array $filters,
        bool $isShared = false,
    ): SavedOpsFilter {
        return SavedOpsFilter::query()->updateOrCreate(
            [
                'organization_id' => (string) $organizationId,
                'user_id' => $userId,
                'name' => $name,
            ],
            [
                'filters_json' => $filters,
                'is_shared' => $isShared,
            ]
        );
    }
}

