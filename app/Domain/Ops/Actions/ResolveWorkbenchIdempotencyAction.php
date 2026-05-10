<?php

namespace App\Domain\Ops\Actions;

use App\Domain\Ops\Models\WorkbenchIdempotencyKey;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class ResolveWorkbenchIdempotencyAction
{
    public function execute(
        string|int $organizationId,
        int $userId,
        string $actionName,
        string $idempotencyKey,
        string $requestHash,
        ?string $targetType = null,
        ?int $targetId = null,
    ): array {
        return DB::transaction(function () use (
            $organizationId,
            $userId,
            $actionName,
            $idempotencyKey,
            $requestHash,
            $targetType,
            $targetId
        ) {
            $record = WorkbenchIdempotencyKey::query()
                ->where('organization_id', (string) $organizationId)
                ->where('user_id', $userId)
                ->where('action_name', $actionName)
                ->where('idempotency_key', $idempotencyKey)
                ->lockForUpdate()
                ->first();

            if (! $record) {
                $createdFresh = false;
                try {
                    $record = WorkbenchIdempotencyKey::query()->create([
                        'organization_id' => (string) $organizationId,
                        'user_id' => $userId,
                        'action_name' => $actionName,
                        'idempotency_key' => $idempotencyKey,
                        'target_type' => $targetType,
                        'target_id' => $targetId,
                        'request_hash' => $requestHash,
                        'state' => 'processing',
                        'started_at' => now(),
                        'expires_at' => now()->addDay(),
                    ]);
                    $createdFresh = true;
                } catch (QueryException $e) {
                    // Concurrent insert race: refetch under lock and continue through normal resolution.
                    if (($e->errorInfo[0] ?? null) !== '23505') {
                        throw $e;
                    }

                    $record = WorkbenchIdempotencyKey::query()
                        ->where('organization_id', (string) $organizationId)
                        ->where('user_id', $userId)
                        ->where('action_name', $actionName)
                        ->where('idempotency_key', $idempotencyKey)
                        ->lockForUpdate()
                        ->first();
                }

                if (! $createdFresh && $record && $record->request_hash === $requestHash && $record->state === 'processing') {
                    return [
                        'mode' => 'processing',
                        'record' => $record,
                        'response' => response()->json([
                            'message' => 'This request is already being processed.',
                        ], 409),
                    ];
                }

                if (! $createdFresh && $record && $record->request_hash !== $requestHash) {
                    return [
                        'mode' => 'conflict',
                        'record' => $record,
                        'response' => response()->json([
                            'message' => 'Idempotency key was already used with a different request payload.',
                        ], 409),
                    ];
                }

                return [
                    'mode' => 'fresh',
                    'record' => $record,
                    'response' => null,
                ];
            }

            if ($record->request_hash !== $requestHash) {
                return [
                    'mode' => 'conflict',
                    'record' => $record,
                    'response' => response()->json([
                        'message' => 'Idempotency key was already used with a different request payload.',
                    ], 409),
                ];
            }

            if ($record->state === 'completed') {
                return [
                    'mode' => 'cached',
                    'record' => $record,
                    'response' => new JsonResponse(
                        $record->response_body_json ?? [],
                        $record->response_status ?? 200
                    ),
                ];
            }

            if ($record->state === 'failed') {
                return [
                    'mode' => 'cached_failed',
                    'record' => $record,
                    'response' => new JsonResponse(
                        $record->response_body_json ?? ['message' => 'Previous request failed.'],
                        $record->response_status ?? 409
                    ),
                ];
            }

            return [
                'mode' => 'processing',
                'record' => $record,
                'response' => response()->json([
                    'message' => 'This request is already being processed.',
                ], 409),
            ];
        });
    }
}
