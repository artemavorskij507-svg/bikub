<?php

namespace App\Http\Controllers\Api\Ops;

use App\Domain\Operations\Models\ServiceJob;
use App\Domain\Ops\Actions\StoreSavedOpsFilterAction;
use App\Domain\Ops\Models\SavedOpsFilter;
use App\Domain\Ops\Queries\SavedOpsFilterQuery;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Ops\Concerns\ResolvesOpsScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SavedOpsFilterController extends Controller
{
    use ResolvesOpsScope;

    public function index(Request $request, SavedOpsFilterQuery $query): JsonResponse
    {
        $this->authorize('viewAny', ServiceJob::class);

        $organizationId = $this->resolveOrganizationScope();

        return response()->json([
            'filters' => $query->execute($organizationId, (int) auth()->id()),
        ]);
    }

    public function store(Request $request, StoreSavedOpsFilterAction $action): JsonResponse
    {
        $this->authorize('viewAny', ServiceJob::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'filters' => ['required', 'array'],
            'is_shared' => ['nullable', 'boolean'],
        ]);

        $organizationId = $this->resolveOrganizationScope();

        $saved = $action->execute(
            organizationId: $organizationId,
            userId: (int) auth()->id(),
            name: (string) $validated['name'],
            filters: (array) $validated['filters'],
            isShared: (bool) ($validated['is_shared'] ?? false),
        );

        return response()->json([
            'id' => $saved->id,
            'name' => $saved->name,
            'filters' => (array) $saved->filters_json,
            'is_shared' => (bool) $saved->is_shared,
        ]);
    }

    public function destroy(SavedOpsFilter $filter): JsonResponse
    {
        $this->authorize('delete', $filter);

        $organizationId = $this->resolveOrganizationScope();
        abort_if((string) $filter->organization_id !== $organizationId, 403, 'Forbidden');

        $filter->delete();

        return response()->json(['deleted' => true]);
    }
}
