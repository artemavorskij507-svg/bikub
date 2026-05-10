<?php

namespace App\Services;

use App\Events\OrderCanceled;
use App\Events\OrderPaid;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Unified service for handling payment webhooks from multiple providers.
 * Supports: Stripe, Vipps, and other payment gateways
 */
class PaymentWebhookService
{
    /**
     * Handle successful payment from any provider.
     *
     * @param  string  $provider  Provider name (stripe, vipps, etc)
     * @param  string  $externalId  External payment ID from provider
     * @param  array  $metadata  Additional metadata from provider
     *
     * @throws \Exception
     */
    public static function markPaymentSuccessful(
        Order $order,
        string $provider,
        string $externalId,
        array $metadata = []
    ): bool {
        return DB::transaction(function () use ($order, $provider, $externalId, $metadata) {
            try {
                // Validate order status is still pending/unpaid
                if ($order->payment_status === 'paid') {
                    Log::info('Payment already marked as successful for order', [
                        'order_id' => $order->id,
                        'provider' => $provider,
                    ]);

                    return true;
                }

                // Update order
                $order->update([
                    'payment_status' => 'paid',
                    'status' => 'confirmed',
                    'payment_method' => $provider,
                    'metadata' => array_merge($order->metadata ?? [], [
                        'payment_provider' => $provider,
                        'payment_external_id' => $externalId,
                        'payment_confirmed_at' => now()->toIso8601String(),
                        'payment_confirmation_raw' => json_encode($metadata),
                    ]),
                ]);

                // Dispatch event for task generation and notifications
                OrderPaid::dispatch($order);

                Log::info('Payment confirmed via '.$provider, [
                    'order_id' => $order->id,
                    'external_payment_id' => $externalId,
                    'amount' => $order->total_amount,
                    'currency' => $order->currency ?? 'NOK',
                ]);

                return true;

            } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
                Log::error('Order not found when marking payment successful', [
                    'order_id' => $order->id ?? 'unknown',
                    'provider' => $provider,
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            } catch (\Exception $e) {
                Log::error('Failed to mark payment successful', [
                    'order_id' => $order->id ?? 'unknown',
                    'provider' => $provider,
                    'external_id' => $externalId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e;
            }
        });
    }

    /**
     * Handle failed payment from any provider.
     *
     * @param  string  $provider  Provider name (stripe, vipps, etc)
     * @param  string|null  $reason  Failure reason from provider
     * @param  array  $metadata  Additional metadata from provider
     */
    public static function markPaymentFailed(
        Order $order,
        string $provider,
        ?string $reason = null,
        array $metadata = []
    ): bool {
        return DB::transaction(function () use ($order, $provider, $reason, $metadata) {
            try {
                // Only update if not already paid
                if ($order->payment_status === 'paid') {
                    Log::warning('Cannot mark payment as failed - already paid', [
                        'order_id' => $order->id,
                        'provider' => $provider,
                    ]);

                    return false;
                }

                $order->update([
                    'payment_status' => 'failed',
                    'status' => 'cancelled',
                    'metadata' => array_merge($order->metadata ?? [], [
                        'payment_provider' => $provider,
                        'payment_failed_at' => now()->toIso8601String(),
                        'payment_failure_reason' => $reason,
                        'payment_failure_raw' => json_encode($metadata),
                    ]),
                ]);

                // Dispatch cancellation event
                OrderCanceled::dispatch($order);

                Log::warning('Payment failed via '.$provider, [
                    'order_id' => $order->id,
                    'reason' => $reason,
                ]);

                return true;

            } catch (\Exception $e) {
                Log::error('Failed to mark payment as failed', [
                    'order_id' => $order->id ?? 'unknown',
                    'provider' => $provider,
                    'error' => $e->getMessage(),
                ]);

                // Don't re-throw - better to log than crash on error handling
                return false;
            }
        });
    }

    /**
     * Handle payment refund from any provider.
     *
     * @param  float  $amount  Refund amount
     * @param  string|null  $reason  Refund reason
     */
    public static function markPaymentRefunded(
        Order $order,
        string $provider,
        float $amount,
        ?string $reason = null,
        array $metadata = []
    ): bool {
        return DB::transaction(function () use ($order, $provider, $amount, $reason, $metadata) {
            try {
                // Validate refund amount
                if ($amount <= 0 || $amount > ($order->total_amount ?? 0)) {
                    throw new \InvalidArgumentException(
                        sprintf('Invalid refund amount: %.2f (order total: %.2f)',
                            $amount,
                            $order->total_amount ?? 0
                        )
                    );
                }

                // Check if full refund
                $isFullRefund = abs($amount - ($order->total_amount ?? 0)) < 0.01;

                $order->update([
                    'payment_status' => $isFullRefund ? 'refunded' : 'partially_refunded',
                    'status' => $isFullRefund ? 'cancelled' : 'confirmed',
                    'metadata' => array_merge($order->metadata ?? [], [
                        'refund_provider' => $provider,
                        'refund_amount' => $amount,
                        'refund_timestamp' => now()->toIso8601String(),
                        'refund_reason' => $reason,
                        'refund_raw' => json_encode($metadata),
                    ]),
                ]);

                Log::info('Payment refunded via '.$provider, [
                    'order_id' => $order->id,
                    'refund_amount' => $amount,
                    'full_refund' => $isFullRefund,
                ]);

                return true;

            } catch (\InvalidArgumentException $e) {
                Log::error('Invalid refund amount', [
                    'order_id' => $order->id,
                    'amount' => $amount,
                    'error' => $e->getMessage(),
                ]);

                return false;

            } catch (\Exception $e) {
                Log::error('Failed to mark payment as refunded', [
                    'order_id' => $order->id,
                    'provider' => $provider,
                    'amount' => $amount,
                    'error' => $e->getMessage(),
                ]);

                return false;
            }
        });
    }

    /**
     * Find order by external payment ID from any provider.
     * Useful for webhook handling when you only have external ID.
     */
    public static function findOrderByExternalPaymentId(string $externalPaymentId): ?Order
    {
        try {
            return Order::whereJsonContains(
                'metadata->payment_external_id',
                $externalPaymentId
            )->first();
        } catch (\Exception $e) {
            Log::error('Failed to find order by external payment ID', [
                'external_id' => $externalPaymentId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Validate webhook signature from provider.
     * Different providers have different signing methods.
     *
     * @param  string  $provider  Provider name
     * @param  string  $payload  Raw payload body
     * @param  string  $signature  Signature from headers
     */
    public static function validateWebhookSignature(
        string $provider,
        string $payload,
        string $signature
    ): bool {
        try {
            return match ($provider) {
                'stripe' => self::validateStripeSignature($payload, $signature),
                'vipps' => self::validateVippsSignature($payload, $signature),
                default => false,
            };
        } catch (\Exception $e) {
            Log::error('Failed to validate webhook signature', [
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Validate Stripe webhook signature using HMAC-SHA256.
     */
    private static function validateStripeSignature(string $payload, string $signature): bool
    {
        $secret = config('services.stripe.webhook_secret');
        if (! $secret) {
            Log::warning('Stripe webhook secret not configured');

            return false;
        }

        $hash = hash_hmac('sha256', $payload, $secret);
        $expectedSignature = 't='.now()->timestamp.',v1='.$hash;

        return hash_equals($signature, $expectedSignature);
    }

    /**
     * Validate Vipps webhook signature.
     */
    private static function validateVippsSignature(string $payload, string $signature): bool
    {
        $secret = config('services.vipps.webhook_secret');
        if (! $secret) {
            Log::warning('Vipps webhook secret not configured');

            return false;
        }

        $hash = hash_hmac('sha256', $payload, $secret);

        return hash_equals($hash, $signature);
    }
}
