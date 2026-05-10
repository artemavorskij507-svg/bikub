<?php

namespace App\Domain\Ops\Actions;

use App\Domain\Ops\Models\WorkbenchIdempotencyKey;
use Symfony\Component\HttpFoundation\Response;

class StoreWorkbenchIdempotencyResultAction
{
    public function complete(WorkbenchIdempotencyKey $record, Response $response): WorkbenchIdempotencyKey
    {
        $payload = json_decode((string) $response->getContent(), true);

        $record->update([
            'state' => 'completed',
            'response_status' => $response->getStatusCode(),
            'response_body_json' => is_array($payload) ? $payload : ['raw' => (string) $response->getContent()],
            'completed_at' => now(),
        ]);

        return $record->fresh();
    }

    public function fail(WorkbenchIdempotencyKey $record, Response $response): WorkbenchIdempotencyKey
    {
        $payload = json_decode((string) $response->getContent(), true);

        $record->update([
            'state' => 'failed',
            'response_status' => $response->getStatusCode(),
            'response_body_json' => is_array($payload) ? $payload : ['raw' => (string) $response->getContent()],
            'completed_at' => now(),
        ]);

        return $record->fresh();
    }

    public function forget(WorkbenchIdempotencyKey $record): void
    {
        $record->delete();
    }
}

