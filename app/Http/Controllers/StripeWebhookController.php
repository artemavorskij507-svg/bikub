<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\PaymentSetting;
use App\Services\PaymentWebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Stripe;
use Stripe\Webhook;

class StripeWebhookController extends Controller
{
    /**
     * Handle Stripe webhook events.
     */
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');

        try {
            // Get webhook secret from payment settings or env
            $paymentSettings = PaymentSetting::getStripeSettings();
            $webhookSecret = $paymentSettings?->webhook_secret ?? env('STRIPE_WEBHOOK_SECRET');

            if (! $webhookSecret) {
                Log::error('Stripe webhook secret not configured');

                return response()->json([
                    'error' => 'Webhook secret not configured',
                ], 400);
            }

            // Verify webhook signature
            $event = Webhook::constructEvent($payload, $sigHeader, $webhookSecret);

        } catch (\UnexpectedValueException $e) {
            Log::error('Stripe webhook: Invalid payload', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Invalid payload',
            ], 400);

        } catch (SignatureVerificationException $e) {
            Log::error('Stripe webhook: Invalid signature', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Invalid signature',
            ], 400);
        } catch (\Exception $e) {
            Log::error('Stripe webhook: Unexpected error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Internal server error',
            ], 500);
        }

        // Handle the event
        try {
            switch ($event->type) {
                case 'payment_intent.succeeded':
                    $this->handlePaymentSucceeded($event->data->object);
                    break;

                case 'payment_intent.payment_failed':
                    $this->handlePaymentFailed($event->data->object);
                    break;

                case 'payment_intent.canceled':
                    $this->handlePaymentCanceled($event->data->object);
                    break;

                case 'charge.refunded':
                    $this->handleChargeRefunded($event->data->object);
                    break;

                case 'payment_intent.requires_action':
                    // Optional: handle 3D secure or other auth requirements
                    Log::info('Stripe webhook: Payment requires action', [
                        'payment_intent_id' => $event->data->object->id,
                    ]);
                    break;

                default:
                    Log::info('Stripe webhook: Unhandled event type', [
                        'type' => $event->type,
                    ]);
            }
        } catch (\Exception $e) {
            Log::error('Stripe webhook: Error processing event', [
                'event_type' => $event->type,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            // Don't return error - Stripe will retry
        }

        return response()->json([
            'status' => 'success',
        ]);
    }

    /**
     * Handle successful payment.
     */
    private function handlePaymentSucceeded($paymentIntent): void
    {
        Log::info('Stripe webhook: Payment succeeded', [
            'payment_intent_id' => $paymentIntent->id,
            'amount' => $paymentIntent->amount,
            'currency' => $paymentIntent->currency,
        ]);

        // Find order by payment intent ID
        $order = Order::whereJsonContains(
            'metadata->payment_intent_id',
            $paymentIntent->id
        )->first();

        if (! $order) {
            Log::warning('Order not found for payment intent', [
                'payment_intent_id' => $paymentIntent->id,
            ]);

            return;
        }

        // Use unified payment webhook service
        try {
            PaymentWebhookService::markPaymentSuccessful(
                $order,
                'stripe',
                $paymentIntent->id,
                (array) $paymentIntent
            );
        } catch (\Exception $e) {
            Log::error('Failed to mark Stripe payment as successful', [
                'order_id' => $order->id,
                'payment_intent_id' => $paymentIntent->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle failed payment.
     */
    private function handlePaymentFailed($paymentIntent): void
    {
        Log::info('Stripe webhook: Payment failed', [
            'payment_intent_id' => $paymentIntent->id,
            'last_payment_error' => $paymentIntent->last_payment_error,
        ]);

        $order = Order::whereJsonContains(
            'metadata->payment_intent_id',
            $paymentIntent->id
        )->first();

        if (! $order) {
            Log::warning('Order not found for failed payment intent', [
                'payment_intent_id' => $paymentIntent->id,
            ]);

            return;
        }

        // Use unified payment webhook service
        try {
            $errorMessage = $paymentIntent->last_payment_error?->message ?? 'Unknown error';
            PaymentWebhookService::markPaymentFailed(
                $order,
                'stripe',
                $errorMessage,
                (array) $paymentIntent->last_payment_error
            );
        } catch (\Exception $e) {
            Log::error('Failed to mark Stripe payment as failed', [
                'order_id' => $order->id,
                'payment_intent_id' => $paymentIntent->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle canceled payment.
     */
    private function handlePaymentCanceled($paymentIntent): void
    {
        Log::info('Stripe webhook: Payment canceled', [
            'payment_intent_id' => $paymentIntent->id,
        ]);

        $order = Order::whereJsonContains(
            'metadata->payment_intent_id',
            $paymentIntent->id
        )->first();

        if (! $order) {
            Log::warning('Order not found for canceled payment intent', [
                'payment_intent_id' => $paymentIntent->id,
            ]);

            return;
        }

        // Use unified payment webhook service
        try {
            PaymentWebhookService::markPaymentFailed(
                $order,
                'stripe',
                'Payment canceled by customer',
                ['reason' => 'customer_canceled']
            );
        } catch (\Exception $e) {
            Log::error('Failed to mark Stripe payment as canceled', [
                'order_id' => $order->id,
                'payment_intent_id' => $paymentIntent->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle refunded charge.
     */
    private function handleChargeRefunded($charge): void
    {
        Log::info('Stripe webhook: Charge refunded', [
            'charge_id' => $charge->id,
            'refund_amount' => $charge->amount_refunded,
        ]);

        // Find order by charge ID
        $order = Order::whereJsonContains(
            'metadata->stripe_charge_id',
            $charge->id
        )->first();

        if (! $order) {
            Log::warning('Order not found for refunded charge', [
                'charge_id' => $charge->id,
            ]);

            return;
        }

        // Use unified payment webhook service
        try {
            $refundAmount = $charge->amount_refunded / 100; // Convert cents to NOK
            PaymentWebhookService::markPaymentRefunded(
                $order,
                'stripe',
                $refundAmount,
                'Refunded via Stripe',
                (array) $charge
            );
        } catch (\Exception $e) {
            Log::error('Failed to mark Stripe payment as refunded', [
                'order_id' => $order->id,
                'charge_id' => $charge->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
