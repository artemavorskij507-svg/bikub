<?php

namespace App\Services;

use App\Models\Order;
use App\Models\PaymentSetting;
use Illuminate\Support\Facades\Log;
use Stripe\Customer;
use Stripe\PaymentIntent;

// Stripe SDK is now installed via composer
// No need for manual loading

class StripePaymentService
{
    protected ?PaymentSetting $settings;

    public function __construct()
    {
        // Stripe SDK is loaded via composer autoload
        $this->settings = PaymentSetting::getStripeSettings();

        if ($this->settings && $this->settings->isConfigured()) {
            \Stripe\Stripe::setApiKey($this->settings->getSecretKey());
        } else {
            // Fallback to env
            $apiKey = env('STRIPE_SECRET_KEY');
            if ($apiKey) {
                \Stripe\Stripe::setApiKey($apiKey);
            }
        }
    }

    /**
     * Create a payment intent for an order.
     *
     * @throws ApiErrorException
     */
    public function createPaymentIntent(Order $order, array $additionalData = []): array
    {
        try {
            $amount = (int) ($order->total_amount * 100); // Convert to øre/cents

            $paymentIntentData = [
                'amount' => $amount,
                'currency' => strtolower($order->currency ?? 'nok'),
                'metadata' => [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'user_id' => $order->user_id,
                ],
            ];

            // Add description
            $paymentIntentData['description'] = "Order #{$order->order_number}";

            // Create customer if email provided
            if (isset($additionalData['email'])) {
                $customer = $this->findOrCreateCustomer($additionalData['email'], [
                    'name' => $additionalData['name'] ?? null,
                ]);

                if ($customer) {
                    $paymentIntentData['customer'] = $customer->id;
                }
            }

            $intent = \Stripe\PaymentIntent::create($paymentIntentData);

            return [
                'success' => true,
                'client_secret' => $intent->client_secret,
                'payment_intent_id' => $intent->id,
                'amount' => $intent->amount,
                'currency' => $intent->currency,
            ];
        } catch (\Stripe\Exception\ApiErrorException $e) {
            Log::error('Stripe Payment Intent Error: '.$e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'error_type' => $e->getError()->type ?? 'unknown',
            ];
        }
    }

    /**
     * Confirm payment intent.
     */
    public function confirmPaymentIntent(string $paymentIntentId, array $options = []): array
    {
        try {
            $intent = \Stripe\PaymentIntent::retrieve($paymentIntentId);
            $intent = empty($options)
                ? $intent->confirm()
                : $intent->confirm($options);

            return [
                'success' => $intent->status === 'succeeded',
                'status' => $intent->status,
                'payment_intent_id' => $intent->id,
            ];
        } catch (\Stripe\Exception\ApiErrorException $e) {
            Log::error('Stripe Payment Confirmation Error: '.$e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get payment intent status.
     */
    public function getPaymentStatus(string $paymentIntentId): array
    {
        try {
            $intent = \Stripe\PaymentIntent::retrieve($paymentIntentId);

            return [
                'success' => true,
                'status' => $intent->status,
                'amount' => $intent->amount,
                'currency' => $intent->currency,
                'metadata' => $intent->metadata,
            ];
        } catch (\Stripe\Exception\ApiErrorException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Refund a payment.
     */
    public function refundPayment(string $paymentIntentId, ?float $amount = null): array
    {
        try {
            $intent = PaymentIntent::retrieve($paymentIntentId);

            $refundData = [
                'payment_intent' => $paymentIntentId,
            ];

            if ($amount) {
                $refundData['amount'] = (int) ($amount * 100);
            }

            $refund = \Stripe\Refund::create($refundData);

            return [
                'success' => true,
                'refund_id' => $refund->id,
                'amount' => $refund->amount / 100,
            ];
        } catch (\Stripe\Exception\ApiErrorException $e) {
            Log::error('Stripe Refund Error: '.$e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Find or create Stripe customer.
     */
    protected function findOrCreateCustomer(string $email, array $additionalData = []): ?Customer
    {
        try {
            // Try to find existing customer
            $customers = \Stripe\Customer::all([
                'email' => $email,
                'limit' => 1,
            ]);

            if (count($customers->data) > 0) {
                return $customers->data[0];
            }

            // Create new customer
            $customerData = [
                'email' => $email,
            ];

            if (isset($additionalData['name'])) {
                $customerData['name'] = $additionalData['name'];
            }

            return \Stripe\Customer::create($customerData);
        } catch (\Stripe\Exception\ApiErrorException $e) {
            Log::warning('Stripe Customer Creation Error: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Get publishable key.
     */
    public function getPublishableKey(): string
    {
        if ($this->settings && $this->settings->isConfigured()) {
            return $this->settings->getPublishableKey();
        }

        return env('STRIPE_PUBLISHABLE_KEY', '');
    }

    /**
     * Check if payment gateway is configured.
     */
    public function isConfigured(): bool
    {
        return ! empty($this->getPublishableKey()) && ! empty(\Stripe\Stripe::getApiKey());
    }
}
