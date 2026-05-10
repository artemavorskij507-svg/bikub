<?php

namespace App\Http\Controllers\Api\Ops;

use App\Domain\Dispatch\Actions\AcquireJobDispatchLockAction;
use App\Domain\Dispatch\Actions\ManualAssignExecutorAction;
use App\Domain\Dispatch\Actions\ManualReassignExecutorAction;
use App\Domain\Dispatch\Actions\ReleaseJobDispatchLockAction;
use App\Domain\Exceptions\Actions\AcknowledgeOperationExceptionAction;
use App\Domain\Exceptions\Actions\ResolveOperationExceptionAction;
use App\Domain\Exceptions\Models\OperationException;
use App\Domain\Ops\Actions\BuildWorkbenchRequestHashAction;
use App\Domain\Ops\Actions\RecordWorkbenchActionAudit;
use App\Domain\Ops\Actions\ResolveWorkbenchIdempotencyAction;
use App\Domain\Ops\Actions\StoreWorkbenchIdempotencyResultAction;
use App\Domain\Ops\Actions\ValidateDrawerVersionAction;
use App\Domain\Ops\Events\WorkbenchActionPerformed;
use App\Domain\Ops\Models\WorkbenchIdempotencyKey;
use App\Domain\Operations\Models\Executor;
use App\Domain\Operations\Models\ServiceJob;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ops\AcknowledgeExceptionRequest;
use App\Http\Requests\Ops\ManualDispatchRequest;
use App\Http\Requests\Ops\ManualReassignRequest;
use App\Http\Requests\Ops\ResolveExceptionRequest;
use App\Support\Ops\WorkbenchErrorPresenter;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class DispatchWorkbenchController extends Controller
{
    public function manualDispatch(
        ManualDispatchRequest $request,
        ServiceJob $job,
        BuildWorkbenchRequestHashAction $buildHash,
        ResolveWorkbenchIdempotencyAction $resolveIdempotency,
        StoreWorkbenchIdempotencyResultAction $storeIdempotency,
        AcquireJobDispatchLockAction $acquireLock,
        ReleaseJobDispatchLockAction $releaseLock,
        ValidateDrawerVersionAction $validateVersion,
        ManualAssignExecutorAction $manualAssignExecutorAction,
        RecordWorkbenchActionAudit $recordAudit,
    ): Response {
        $user = $request->user();
        $organizationId = (string) ($user->organization_id ?? $job->organization_id);
        $actionName = 'manual_dispatch';
        $idempotencyKey = (string) $request->header('X-Idempotency-Key');
        $requestHash = $buildHash->execute($request, $actionName, ['job_id' => $job->id]);

        $resolved = $resolveIdempotency->execute(
            organizationId: $organizationId,
            userId: (int) $user->id,
            actionName: $actionName,
            idempotencyKey: $idempotencyKey,
            requestHash: $requestHash,
            targetType: 'service_job',
            targetId: (int) $job->id,
        );

        if ($resolved['mode'] !== 'fresh') {
            return $resolved['response'];
        }

        /** @var WorkbenchIdempotencyKey $idempotencyRecord */
        $idempotencyRecord = $resolved['record'];
        $lock = null;

        try {
            $lock = $acquireLock->execute((int) $job->id);

            $validateVersion->execute(
                expectedVersion: (string) $request->validated('expected_job_version'),
                actualVersion: $job->updated_at,
            );

            $executorId = (int) $request->validated('executor_id');
            /** @var Executor $executor */
            $executor = Executor::query()
                ->where('organization_id', $job->organization_id)
                ->findOrFail($executorId);

            $assignment = $manualAssignExecutorAction->execute(
                job: $job,
                executor: $executor,
                dispatcherUserId: (int) $user->id,
                reason: $request->validated('notes'),
            );

            $job->refresh();
            $response = response()->json([
                'message' => 'Job manually assigned.',
                'job_id' => $job->id,
                'assignment_id' => $assignment->id,
                'executor_id' => $executor->id,
                'entity_updated_at' => optional($job->updated_at)->toIso8601String(),
                'drawer_version' => optional($job->updated_at)?->format('Y-m-d H:i:s.u'),
            ], 200);

            $storeIdempotency->complete($idempotencyRecord, $response);

            $recordAudit->execute(
                actorUserId: (int) $user->id,
                action: 'manual_dispatch_requested',
                success: true,
                targetType: 'service_job',
                targetId: (int) $job->id,
                payload: $request->validated(),
                jobId: (int) $job->id,
                executorId: (int) $executor->id,
            );

            event(new WorkbenchActionPerformed(
                organizationId: $organizationId,
                actorUserId: (int) $user->id,
                action: 'manual_dispatch_requested',
                success: true,
                jobId: (int) $job->id,
                executorId: (int) $executor->id,
                message: 'Job manually assigned.',
            ));

            return $response;
        } catch (Throwable $e) {
            $response = response()->json([
                'message' => WorkbenchErrorPresenter::message($e),
                'code' => WorkbenchErrorPresenter::code($e),
            ], WorkbenchErrorPresenter::status($e));

            if (WorkbenchErrorPresenter::status($e) >= 500) {
                $storeIdempotency->forget($idempotencyRecord);
            } else {
                $storeIdempotency->fail($idempotencyRecord, $response);
            }

            $recordAudit->execute(
                actorUserId: (int) $user->id,
                action: 'manual_dispatch_requested',
                success: false,
                targetType: 'service_job',
                targetId: (int) $job->id,
                payload: $request->validated(),
                jobId: (int) $job->id,
                message: $e->getMessage(),
            );

            event(new WorkbenchActionPerformed(
                organizationId: $organizationId,
                actorUserId: (int) $user->id,
                action: 'manual_dispatch_requested',
                success: false,
                jobId: (int) $job->id,
                message: WorkbenchErrorPresenter::message($e),
                payload: ['reason' => $e->getMessage()],
            ));

            return $response;
        } finally {
            if ($lock) {
                $releaseLock->execute($lock['key'] ?? null, $lock['owner_token'] ?? null);
            }
        }
    }

    public function manualReassign(
        ManualReassignRequest $request,
        ServiceJob $job,
        BuildWorkbenchRequestHashAction $buildHash,
        ResolveWorkbenchIdempotencyAction $resolveIdempotency,
        StoreWorkbenchIdempotencyResultAction $storeIdempotency,
        AcquireJobDispatchLockAction $acquireLock,
        ReleaseJobDispatchLockAction $releaseLock,
        ValidateDrawerVersionAction $validateVersion,
        ManualReassignExecutorAction $manualReassignExecutorAction,
        RecordWorkbenchActionAudit $recordAudit,
    ): Response {
        $user = $request->user();
        $organizationId = (string) ($user->organization_id ?? $job->organization_id);
        $actionName = 'manual_reassign';
        $idempotencyKey = (string) $request->header('X-Idempotency-Key');
        $requestHash = $buildHash->execute($request, $actionName, ['job_id' => $job->id]);

        $resolved = $resolveIdempotency->execute(
            organizationId: $organizationId,
            userId: (int) $user->id,
            actionName: $actionName,
            idempotencyKey: $idempotencyKey,
            requestHash: $requestHash,
            targetType: 'service_job',
            targetId: (int) $job->id,
        );

        if ($resolved['mode'] !== 'fresh') {
            return $resolved['response'];
        }

        /** @var WorkbenchIdempotencyKey $idempotencyRecord */
        $idempotencyRecord = $resolved['record'];
        $lock = null;

        try {
            $lock = $acquireLock->execute((int) $job->id);

            $validateVersion->execute(
                expectedVersion: (string) $request->validated('expected_job_version'),
                actualVersion: $job->updated_at,
            );

            $executorId = (int) $request->validated('executor_id');
            /** @var Executor $executor */
            $executor = Executor::query()
                ->where('organization_id', $job->organization_id)
                ->findOrFail($executorId);

            $assignment = $manualReassignExecutorAction->execute(
                job: $job,
                newExecutor: $executor,
                dispatcherUserId: (int) $user->id,
                reason: $request->validated('reason'),
            );

            $job->refresh();
            $response = response()->json([
                'message' => 'Job manually reassigned.',
                'job_id' => $job->id,
                'assignment_id' => $assignment->id,
                'executor_id' => $executor->id,
                'entity_updated_at' => optional($job->updated_at)->toIso8601String(),
                'drawer_version' => optional($job->updated_at)?->format('Y-m-d H:i:s.u'),
            ], 200);

            $storeIdempotency->complete($idempotencyRecord, $response);

            $recordAudit->execute(
                actorUserId: (int) $user->id,
                action: 'manual_reassign_requested',
                success: true,
                targetType: 'service_job',
                targetId: (int) $job->id,
                payload: $request->validated(),
                jobId: (int) $job->id,
                executorId: (int) $executor->id,
            );

            event(new WorkbenchActionPerformed(
                organizationId: $organizationId,
                actorUserId: (int) $user->id,
                action: 'manual_reassign_requested',
                success: true,
                jobId: (int) $job->id,
                executorId: (int) $executor->id,
                message: 'Job manually reassigned.',
            ));

            return $response;
        } catch (Throwable $e) {
            $response = response()->json([
                'message' => WorkbenchErrorPresenter::message($e),
                'code' => WorkbenchErrorPresenter::code($e),
            ], WorkbenchErrorPresenter::status($e));

            if (WorkbenchErrorPresenter::status($e) >= 500) {
                $storeIdempotency->forget($idempotencyRecord);
            } else {
                $storeIdempotency->fail($idempotencyRecord, $response);
            }

            $recordAudit->execute(
                actorUserId: (int) $user->id,
                action: 'manual_reassign_requested',
                success: false,
                targetType: 'service_job',
                targetId: (int) $job->id,
                payload: $request->validated(),
                jobId: (int) $job->id,
                message: $e->getMessage(),
            );

            event(new WorkbenchActionPerformed(
                organizationId: $organizationId,
                actorUserId: (int) $user->id,
                action: 'manual_reassign_requested',
                success: false,
                jobId: (int) $job->id,
                message: WorkbenchErrorPresenter::message($e),
                payload: ['reason' => $e->getMessage()],
            ));

            return $response;
        } finally {
            if ($lock) {
                $releaseLock->execute($lock['key'] ?? null, $lock['owner_token'] ?? null);
            }
        }
    }

    public function acknowledgeException(
        AcknowledgeExceptionRequest $request,
        OperationException $exception,
        BuildWorkbenchRequestHashAction $buildHash,
        ResolveWorkbenchIdempotencyAction $resolveIdempotency,
        StoreWorkbenchIdempotencyResultAction $storeIdempotency,
        ValidateDrawerVersionAction $validateVersion,
        AcknowledgeOperationExceptionAction $acknowledgeOperationExceptionAction,
        RecordWorkbenchActionAudit $recordAudit,
    ): Response {
        $user = $request->user();
        $organizationId = (string) ($user->organization_id ?? $exception->organization_id);
        $actionName = 'exception_acknowledge';
        $idempotencyKey = (string) $request->header('X-Idempotency-Key');
        $requestHash = $buildHash->execute($request, $actionName, ['exception_id' => $exception->id]);

        $resolved = $resolveIdempotency->execute(
            organizationId: $organizationId,
            userId: (int) $user->id,
            actionName: $actionName,
            idempotencyKey: $idempotencyKey,
            requestHash: $requestHash,
            targetType: 'operation_exception',
            targetId: (int) $exception->id,
        );

        if ($resolved['mode'] !== 'fresh') {
            return $resolved['response'];
        }

        /** @var WorkbenchIdempotencyKey $idempotencyRecord */
        $idempotencyRecord = $resolved['record'];

        try {
            $validateVersion->execute(
                expectedVersion: (string) $request->validated('expected_exception_version'),
                actualVersion: $exception->updated_at,
            );

            $exception = $acknowledgeOperationExceptionAction->execute($exception, (int) $user->id);

            $response = response()->json([
                'message' => 'Exception acknowledged.',
                'exception_id' => $exception->id,
                'status' => $exception->status,
                'entity_updated_at' => optional($exception->updated_at)->toIso8601String(),
                'drawer_version' => optional($exception->updated_at)?->format('Y-m-d H:i:s.u'),
            ], 200);

            $storeIdempotency->complete($idempotencyRecord, $response);

            $recordAudit->execute(
                actorUserId: (int) $user->id,
                action: 'exception_acknowledged_from_map',
                success: true,
                targetType: 'operation_exception',
                targetId: (int) $exception->id,
                payload: $request->validated(),
                exceptionId: (int) $exception->id,
                jobId: (int) ($exception->service_job_id ?? 0) ?: null,
            );

            event(new WorkbenchActionPerformed(
                organizationId: $organizationId,
                actorUserId: (int) $user->id,
                action: 'exception_acknowledged_from_map',
                success: true,
                exceptionId: (int) $exception->id,
                jobId: (int) ($exception->service_job_id ?? 0) ?: null,
                message: 'Exception acknowledged.',
            ));

            return $response;
        } catch (Throwable $e) {
            $response = response()->json([
                'message' => WorkbenchErrorPresenter::message($e),
                'code' => WorkbenchErrorPresenter::code($e),
            ], WorkbenchErrorPresenter::status($e));

            if (WorkbenchErrorPresenter::status($e) >= 500) {
                $storeIdempotency->forget($idempotencyRecord);
            } else {
                $storeIdempotency->fail($idempotencyRecord, $response);
            }

            $recordAudit->execute(
                actorUserId: (int) $user->id,
                action: 'exception_acknowledged_from_map',
                success: false,
                targetType: 'operation_exception',
                targetId: (int) $exception->id,
                payload: $request->validated(),
                exceptionId: (int) $exception->id,
                jobId: (int) ($exception->service_job_id ?? 0) ?: null,
                message: $e->getMessage(),
            );

            return $response;
        }
    }

    public function resolveException(
        ResolveExceptionRequest $request,
        OperationException $exception,
        BuildWorkbenchRequestHashAction $buildHash,
        ResolveWorkbenchIdempotencyAction $resolveIdempotency,
        StoreWorkbenchIdempotencyResultAction $storeIdempotency,
        ValidateDrawerVersionAction $validateVersion,
        ResolveOperationExceptionAction $resolveOperationExceptionAction,
        RecordWorkbenchActionAudit $recordAudit,
    ): Response {
        $user = $request->user();
        $organizationId = (string) ($user->organization_id ?? $exception->organization_id);
        $actionName = 'exception_resolve';
        $idempotencyKey = (string) $request->header('X-Idempotency-Key');
        $requestHash = $buildHash->execute($request, $actionName, ['exception_id' => $exception->id]);

        $resolved = $resolveIdempotency->execute(
            organizationId: $organizationId,
            userId: (int) $user->id,
            actionName: $actionName,
            idempotencyKey: $idempotencyKey,
            requestHash: $requestHash,
            targetType: 'operation_exception',
            targetId: (int) $exception->id,
        );

        if ($resolved['mode'] !== 'fresh') {
            return $resolved['response'];
        }

        /** @var WorkbenchIdempotencyKey $idempotencyRecord */
        $idempotencyRecord = $resolved['record'];

        try {
            $validateVersion->execute(
                expectedVersion: (string) $request->validated('expected_exception_version'),
                actualVersion: $exception->updated_at,
            );

            $exception = $resolveOperationExceptionAction->execute(
                exception: $exception,
                userId: (int) $user->id,
                resolutionCode: (string) $request->validated('resolution_code'),
                resolutionNotes: $request->validated('resolution_notes'),
                rootCause: $request->validated('root_cause'),
            );

            $response = response()->json([
                'message' => 'Exception resolved.',
                'exception_id' => $exception->id,
                'status' => $exception->status,
                'entity_updated_at' => optional($exception->updated_at)->toIso8601String(),
                'drawer_version' => optional($exception->updated_at)?->format('Y-m-d H:i:s.u'),
            ], 200);

            $storeIdempotency->complete($idempotencyRecord, $response);

            $recordAudit->execute(
                actorUserId: (int) $user->id,
                action: 'exception_resolved_from_map',
                success: true,
                targetType: 'operation_exception',
                targetId: (int) $exception->id,
                payload: $request->validated(),
                exceptionId: (int) $exception->id,
                jobId: (int) ($exception->service_job_id ?? 0) ?: null,
            );

            event(new WorkbenchActionPerformed(
                organizationId: $organizationId,
                actorUserId: (int) $user->id,
                action: 'exception_resolved_from_map',
                success: true,
                exceptionId: (int) $exception->id,
                jobId: (int) ($exception->service_job_id ?? 0) ?: null,
                message: 'Exception resolved.',
            ));

            return $response;
        } catch (Throwable $e) {
            $response = response()->json([
                'message' => WorkbenchErrorPresenter::message($e),
                'code' => WorkbenchErrorPresenter::code($e),
            ], WorkbenchErrorPresenter::status($e));

            if (WorkbenchErrorPresenter::status($e) >= 500) {
                $storeIdempotency->forget($idempotencyRecord);
            } else {
                $storeIdempotency->fail($idempotencyRecord, $response);
            }

            $recordAudit->execute(
                actorUserId: (int) $user->id,
                action: 'exception_resolved_from_map',
                success: false,
                targetType: 'operation_exception',
                targetId: (int) $exception->id,
                payload: $request->validated(),
                exceptionId: (int) $exception->id,
                jobId: (int) ($exception->service_job_id ?? 0) ?: null,
                message: $e->getMessage(),
            );

            return $response;
        }
    }
}
