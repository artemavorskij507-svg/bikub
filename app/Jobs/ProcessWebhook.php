<?php

namespace App\Jobs;

use App\Models\WebhookLog;
use App\Services\AuditLogger;
use App\Services\CorrelationService;
use GuzzleHttp\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $logId;

    public function __construct(int $logId)
    {
        $this->logId = $logId;
    }

    public function handle()
    {
        $log = WebhookLog::find($this->logId);
        if (! $log) {
            return;
        }

        try {
            // Example: forward some events to n8n or internal handler
            if ($log->provider === 'n8n') {
                // Forward to n8n webhook URL (configured via env)
                $n8nUrl = config('services.n8n.webhook_url');
                if ($n8nUrl) {
                    $client = new Client(['timeout' => 10]);
                    $response = $client->post($n8nUrl, [
                        'json' => $log->payload,
                        'headers' => ['X-Request-Id' => $log->request_id],
                    ]);
                    $log->http_status = $response->getStatusCode();
                }
            }

            // Mark processed
            $log->status = 'processed';
            $log->processed_at = now();
            $log->attempt = $log->attempt + 1;
            $log->save();

            // Non-blocking correlation: try to link webhook to business entities
            // This doesn't block webhook processing if correlation fails
            try {
                $correlation = app(CorrelationService::class)->correlate($log);
                app(CorrelationService::class)->updateWebhookLog($log, $correlation);
            } catch (\Throwable $e) {
                Log::warning('Correlation failed for webhook', [
                    'webhook_id' => $log->id,
                    'error' => $e->getMessage(),
                ]);
                // Don't throw - correlation is non-blocking
            }

            // Audit: processed
            app(AuditLogger::class)->log('webhook_processed', WebhookLog::class, $log->id, null, ['status' => 'processed'], request());
        } catch (\Throwable $e) {
            $log->status = 'failed';
            $log->error_message = substr($e->getMessage(), 0, 1000);
            $log->processed_at = now();
            $log->attempt = $log->attempt + 1;
            $log->save();

            app(AuditLogger::class)->log('webhook_failed', WebhookLog::class, $log->id, null, ['error' => $e->getMessage()], request());

            Log::error('ProcessWebhook failed', ['id' => $this->logId, 'error' => $e->getMessage()]);

            // Let the job fail for retry if needed
            throw $e;
        }
    }
}
