<?php

namespace App\Domain\Ops\Queries;

use App\Domain\Ops\Models\SavedOpsFilter;

class SavedOpsFilterQuery
{
    public function execute(string $organizationId, int $userId): array
    {
        return SavedOpsFilter::query()
            ->where('organization_id', $organizationId)
            ->where(function ($query) use ($userId): void {
                $query->where('user_id', $userId)->orWhere('is_shared', true);
            })
            ->orderByDesc('is_shared')
            ->orderBy('name')
            ->get()
            ->map(fn (SavedOpsFilter $filter): array => [
                'id' => $filter->id,
                'name' => $filter->name,
                'filters' => (array) $filter->filters_json,
                'is_shared' => (bool) $filter->is_shared,
                'user_id' => (int) $filter->user_id,
            ])
            ->all();
    }
}
