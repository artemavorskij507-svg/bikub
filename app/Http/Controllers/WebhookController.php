<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessWebhook;
use App\Models\WebhookLog;
use App\Services\AuditLogger;
use App\Services\WebhookSignatureValidator;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Log;

class WebhookController extends BaseController
{
    public function receive(string $provider, Request $request)
    {
        $payload = $request->getContent();
        $json = null;
        try {
            $json = json_decode($payload, true);
        } catch (\Throwable $e) {
            // leave as null
        }

        $eventType = $json['type'] ?? $request->header('X-Event-Type') ?? null;
        $externalId = $json['id'] ?? $request->header('X-Event-Id') ?? null;
        $requestId = $request->header('X-Request-Id') ?? ($json['request_id'] ?? null) ?? (string) \Illuminate\Support\Str::uuid();

        // Validate signature before saving
        $validator = app(WebhookSignatureValidator::class);
        $isValid = $validator->verify($provider, $payload, $request);

        if (! $isValid) {
            // Save failed webhook with error
            $log = WebhookLog::create([
                'provider' => $provider,
                'event_type' => $eventType,
                'external_id' => $externalId,
                'status' => 'failed',
                'http_status' => 401,
                'payload' => $json ?? ['raw' => substr($payload, 0, 1000)],
                'error_message' => 'Signature validation failed',
                'request_id' => $requestId,
                'received_at' => now(),
                'attempt' => 0,
            ]);

            // Audit: signature validation failed
            try {
                app(AuditLogger::class)->log(
                    'webhook_signature_invalid',
                    WebhookLog::class,
                    $log->id,
                    null,
                    ['provider' => $provider, 'event' => $eventType],
                    $request
                );
            } catch (\Throwable $e) {
                Log::warning('Audit log failed for webhook_signature_invalid', ['error' => $e->getMessage()]);
            }

            return response()->json(['error' => 'Signature validation failed'], 401);
        }

        // Signature valid - save as received and dispatch job
        $log = WebhookLog::create([
            'provider' => $provider,
            'event_type' => $eventType,
            'external_id' => $externalId,
            'status' => 'received',
            'http_status' => null,
            'payload' => $json ?? ['raw' => $payload],
            'error_message' => null,
            'request_id' => $requestId,
            'received_at' => now(),
            'attempt' => 0,
        ]);

        // Audit: incoming webhook received
        try {
            app(AuditLogger::class)->log(
                'webhook_received',
                WebhookLog::class,
                $log->id,
                null,
                ['provider' => $provider, 'event' => $eventType],
                $request
            );
        } catch (\Throwable $e) {
            Log::warning('Audit log failed for webhook_received', ['error' => $e->getMessage()]);
        }

        // Dispatch processing job
        ProcessWebhook::dispatch($log->id)->onQueue('webhooks');

        return response()->json(['status' => 'accepted', 'id' => $log->id], 202);
    }
}
