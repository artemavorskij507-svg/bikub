<?php

namespace App\Http\Controllers\Api\Ops;

use App\Domain\Operations\Models\ServiceJob;
use App\Domain\Ops\Actions\ApplyWorkbenchBulkAction;
use App\Domain\Ops\Queries\WorkbenchTriageQuery;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Ops\Concerns\ResolvesOpsScope;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkbenchBulkActionController extends Controller
{
    use ResolvesOpsScope;

    public function triage(Request $request, WorkbenchTriageQuery $query): JsonResponse
    {
        $this->authorize('viewAny', ServiceJob::class);

        $organizationId = $this->resolveOrganizationScope();

        return response()->json($query->execute($organizationId));
    }

    public function apply(Request $request, ApplyWorkbenchBulkAction $action): JsonResponse
    {
        $this->authorize('viewAny', ServiceJob::class);

        $validated = $request->validate([
            'action' => ['required', 'string', 'max:100'],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'min:1'],
        ]);

        /** @var User|null $user */
        $user = auth()->user();
        abort_unless($user !== null, 401, 'Unauthenticated');

        $bulkAction = (string) $validated['action'];
        $this->authorizeBulkAction($user, $bulkAction);

        $organizationId = $this->resolveOrganizationScope();

        $result = $action->execute(
            organizationId: $organizationId,
            action: $bulkAction,
            ids: (array) $validated['ids'],
            actorUserId: (int) $user->id,
        );

        return response()->json($result);
    }

    private function authorizeBulkAction(User $user, string $action): void
    {
        $canManageExceptions = $user->hasRole('admin')
            || $user->hasPermission('ops.exceptions.update')
            || $user->hasPermission('ops.exceptions.resolve')
            || $user->can('ops.exceptions.update')
            || $user->can('ops.exceptions.resolve');

        $canManageJobs = $user->hasRole('admin')
            || $user->hasPermission('ops.service_jobs.update')
            || $user->can('ops.service_jobs.update');

        if (in_array($action, ['exceptions_bulk_acknowledge', 'exceptions_bulk_resolve'], true)) {
            abort_unless($canManageExceptions, 403, 'Forbidden');

            return;
        }

        if (in_array($action, ['jobs_bulk_reassign_request', 'jobs_bulk_assign_dispatcher_queue'], true)) {
            abort_unless($canManageJobs, 403, 'Forbidden');

            return;
        }

        abort(422, 'Unsupported bulk action');
    }
}
