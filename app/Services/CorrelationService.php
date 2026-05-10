<?php

namespace App\Services;

use App\Models\WebhookLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class CorrelationService
{
    /**
     * Correlate webhook with business entities (orders, payments)
     * Returns array with matched entities and confidence scores
     */
    public function correlate(WebhookLog $log): array
    {
        $result = [
            'order_id' => null,
            'payment_id' => null,
            'related_type' => null,
            'related_id' => null,
            'context' => [],
            'matched_fields' => [],
            'confidence' => 0,
        ];

        if (! $log->payload || ! is_array($log->payload)) {
            return $result;
        }

        try {
            return match ($log->provider) {
                'stripe' => $this->correlateStripe($log, $result),
                'n8n' => $this->correlateN8n($log, $result),
                default => $result,
            };
        } catch (\Throwable $e) {
            Log::warning('Correlation failed for webhook', [
                'webhook_id' => $log->id,
                'provider' => $log->provider,
                'error' => $e->getMessage(),
            ]);

            return $result;
        }
    }

    /**
     * Correlate Stripe webhook events
     */
    private function correlateStripe(WebhookLog $log, array $result): array
    {
        $payload = $log->payload;

        // Try to find payment by payment_intent.id (payments.provider_ref)
        if (isset($payload['data']['object']['payment_intent']) && Schema::hasTable('payments')) {
            $paymentIntentId = $payload['data']['object']['payment_intent'];
            $payment = DB::table('payments')
                ->where('provider_ref', $paymentIntentId)
                ->where('provider', 'stripe')
                ->first();

            if ($payment) {
                $result['payment_id'] = $payment->id;
                $result['matched_fields'][] = 'payment_intent.id';
                $result['confidence'] += 40;
            }
        }

        // Try to find payment by charge.id
        if (isset($payload['data']['object']['id']) && $payload['type'] === 'charge.completed' && Schema::hasTable('payments')) {
            $chargeId = $payload['data']['object']['id'];
            // charges may be stored in provider_ref or in payment metadata
            $payment = DB::table('payments')
                ->where(function ($q) use ($chargeId) {
                    $q->where('provider_ref', $chargeId)
                        ->orWhereRaw("metadata->>'payment_external_id' = ?", [$chargeId]);
                })
                ->where('provider', 'stripe')
                ->first();

            if ($payment) {
                $result['payment_id'] = $payment->id;
                $result['matched_fields'][] = 'charge.id';
                $result['confidence'] += 30;
            }
        }

        // Try to find order by metadata.order_id
        if (isset($payload['data']['object']['metadata']['order_id']) && Schema::hasTable('orders')) {
            $orderId = (int) $payload['data']['object']['metadata']['order_id'];
            $order = DB::table('orders')
                ->where('id', $orderId)
                ->first();

            if ($order) {
                $result['order_id'] = $orderId;
                $result['matched_fields'][] = 'metadata.order_id';
                $result['confidence'] += 50; // metadata is most reliable
            }
        }

        // Context: store event type and amount
        $result['context'] = [
            'event_type' => $payload['type'] ?? null,
            'amount' => $payload['data']['object']['amount'] ?? null,
            'currency' => $payload['data']['object']['currency'] ?? null,
            'customer_id' => $payload['data']['object']['customer'] ?? null,
        ];

        // Set related_type and related_id for linking
        if ($result['order_id']) {
            $result['related_type'] = 'order';
            $result['related_id'] = $result['order_id'];
        } elseif ($result['payment_id']) {
            $result['related_type'] = 'payment';
            $result['related_id'] = $result['payment_id'];
        }

        return $result;
    }

    /**
     * Correlate n8n webhook events
     */
    private function correlateN8n(WebhookLog $log, array $result): array
    {
        $payload = $log->payload;

        // Try to find order by order_id in payload
        if (isset($payload['order_id']) && Schema::hasTable('orders')) {
            $orderId = (int) $payload['order_id'];
            $order = DB::table('orders')
                ->where('id', $orderId)
                ->first();

            if ($order) {
                $result['order_id'] = $orderId;
                $result['matched_fields'][] = 'order_id';
                $result['confidence'] += 45;
            }
        }

        // Try to find by service_slug
        if (isset($payload['service_slug']) && Schema::hasTable('services')) {
            $serviceSlug = $payload['service_slug'];
            $service = DB::table('services')
                ->where('slug', $serviceSlug)
                ->first();

            if ($service) {
                $result['context']['service_id'] = $service->id;
                $result['matched_fields'][] = 'service_slug';
                $result['confidence'] += 20;
            }
        }

        // Try to find by executor_id
        if (isset($payload['executor_id']) && Schema::hasTable('users')) {
            $executorId = (int) $payload['executor_id'];
            $executor = DB::table('users')
                ->where('id', $executorId)
                ->first();

            if ($executor) {
                $result['context']['executor_id'] = $executorId;
                $result['matched_fields'][] = 'executor_id';
                $result['confidence'] += 15;
            }
        }

        // Context: workflow information
        $result['context'] = array_merge($result['context'] ?? [], [
            'workflow_name' => $payload['workflow_name'] ?? null,
            'execution_status' => $payload['status'] ?? null,
            'timestamp' => $payload['timestamp'] ?? null,
        ]);

        // Set related_type and related_id
        if ($result['order_id']) {
            $result['related_type'] = 'order';
            $result['related_id'] = $result['order_id'];
        }

        return $result;
    }

    /**
     * Update webhook log with correlation data
     * This is non-blocking - if update fails, webhook still processes successfully
     */
    public function updateWebhookLog(WebhookLog $log, array $correlation): void
    {
        try {
            $updateData = [
                'order_id' => $correlation['order_id'],
            ];

            // payment_id column is integer; if we have a non-numeric (UUID) id, store it in metadata instead
            $metaCorrelation = [
                'matched_fields' => $correlation['matched_fields'],
                'confidence' => $correlation['confidence'],
                'context' => $correlation['context'],
                'related_type' => $correlation['related_type'],
                'related_id' => $correlation['related_id'],
            ];

            if (! empty($correlation['payment_id'])) {
                if (is_numeric($correlation['payment_id'])) {
                    $updateData['payment_id'] = (int) $correlation['payment_id'];
                    $metaCorrelation['payment_id'] = (int) $correlation['payment_id'];
                } else {
                    // store payment uuid/reference inside metadata
                    $metaCorrelation['payment_uuid'] = (string) $correlation['payment_id'];
                }
            }

            $updateData['metadata'] = [
                'correlation' => $metaCorrelation,
            ];

            $log->update($updateData);
        } catch (\Throwable $e) {
            Log::warning('Failed to update webhook log with correlation', [
                'webhook_id' => $log->id,
                'error' => $e->getMessage(),
            ]);
            // Don't throw - this is non-blocking
        }
    }
}
