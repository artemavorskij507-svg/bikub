<?php

namespace App\Http\Controllers;

use App\Events\OrderPaid;
use App\Models\Order;
use App\Models\PaymentEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class VippsController extends Controller
{
    private string $baseUrl;

    private ?string $clientId;

    private ?string $clientSecret;

    private ?string $subscriptionKey;

    private ?string $merchantSerialNumber;

    public function __construct()
    {
        $this->baseUrl = config('services.vipps.base_url', 'https://api.vipps.no');
        $this->clientId = config('services.vipps.client_id');
        $this->clientSecret = config('services.vipps.client_secret');
        $this->subscriptionKey = config('services.vipps.subscription_key');
        $this->merchantSerialNumber = config('services.vipps.merchant_serial_number');
    }

    /**
     * Initialize Vipps payment.
     */
    public function initPayment(Request $request)
    {
        if (! $this->isVippsConfigured()) {
            return $this->vippsNotConfiguredResponse();
        }

        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
            'phone_number' => 'required|string|regex:/^\+47[0-9]{8}$/',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $order = Order::findOrFail($request->order_id);

        // Check if order is already paid
        if ($order->payment_status === 'paid') {
            return response()->json([
                'success' => false,
                'message' => 'Order is already paid',
            ], 400);
        }

        try {
            // Get access token
            $accessToken = $this->getAccessToken();

            // Create payment
            $paymentData = [
                'amount' => [
                    'currency' => 'NOK',
                    'value' => $order->total_amount * 100, // Convert to øre
                ],
                'userFlow' => 'WEB_REDIRECT',
                'paymentDescription' => "GLF BiKube Order #{$order->order_number}",
                'userInfo' => [
                    'mobileNumber' => $request->phone_number,
                ],
                'merchantInfo' => [
                    'callbackPrefix' => config('app.url').'/api/v1/payments/vipps/callback',
                    'fallBack' => config('app.url').'/order/'.$order->id.'/vipps/fallback',
                    'consentRemovalPrefix' => config('app.url').'/api/v1/payments/vipps/consent-removal',
                    'isApp' => false,
                    'paymentType' => 'eComm Regular Payment',
                    'shippingDetailsPrefix' => config('app.url').'/api/v1/payments/vipps/shipping-details',
                    'staticShippingDetails' => [
                        'isDefault' => true,
                        'priority' => 1,
                        'shippingMethod' => 'Standard delivery',
                        'shippingMethodId' => 'standard',
                        'fixedPrice' => 0,
                        'variablePrice' => 0,
                        'shippingMethodDescription' => 'Standard delivery',
                    ],
                ],
                'transaction' => [
                    'orderId' => $order->order_number,
                    'transactionText' => "GLF BiKube Order #{$order->order_number}",
                ],
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$accessToken,
                'Ocp-Apim-Subscription-Key' => $this->subscriptionKey,
                'Merchant-Serial-Number' => $this->merchantSerialNumber,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl.'/epayment/v1/payments', $paymentData);

            if (! $response->successful()) {
                throw new \Exception('Vipps API error: '.$response->body());
            }

            $paymentResponse = $response->json();

            // Update order with Vipps payment info
            $order->update([
                'payment_provider' => 'vipps',
                'payment_provider_ref' => $paymentResponse['reference'],
                'payment_meta' => [
                    'vipps_payment_id' => $paymentResponse['reference'],
                    'initiated_at' => now()->toISOString(),
                ],
            ]);

            // Log payment event
            PaymentEvent::create([
                'idempotency_key' => 'vipps_init_'.$order->id.'_'.time(),
                'provider' => 'vipps',
                'type' => 'payment_initiated',
                'payload' => $paymentResponse,
                'status' => 'processed',
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'payment_url' => $paymentResponse['redirectUrl'],
                    'reference' => $paymentResponse['reference'],
                    'order_id' => $order->id,
                ],
                'message' => 'Vipps payment initialized successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Vipps payment initiation failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to initialize Vipps payment',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Handle Vipps callback.
     */
    public function handleCallback(Request $request)
    {
        if (! $this->isVippsConfigured()) {
            return $this->vippsNotConfiguredResponse();
        }

        $validator = Validator::make($request->all(), [
            'reference' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $reference = $request->reference;

        try {
            // Find order by Vipps reference
            $order = Order::where('payment_provider_ref', $reference)->first();

            if (! $order) {
                Log::warning('Vipps callback: Order not found', ['reference' => $reference]);

                return response()->json(['success' => false, 'message' => 'Order not found'], 404);
            }

            // Get payment status from Vipps
            $paymentStatus = $this->getPaymentStatus($reference);

            // Update order based on payment status
            $this->updateOrderPaymentStatus($order, $paymentStatus);

            // Log payment event
            PaymentEvent::create([
                'idempotency_key' => 'vipps_callback_'.$reference.'_'.time(),
                'provider' => 'vipps',
                'type' => 'payment_callback',
                'payload' => $paymentStatus,
                'status' => 'processed',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Callback processed successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Vipps callback processing failed', [
                'reference' => $reference,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Callback processing failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Handle Vipps webhook.
     */
    public function handleWebhook(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'reference' => 'required|string',
            'event' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $reference = $request->reference;
        $event = $request->event;

        try {
            // Find order by Vipps reference
            $order = Order::where('payment_provider_ref', $reference)->first();

            if (! $order) {
                Log::warning('Vipps webhook: Order not found', ['reference' => $reference]);

                return response()->json(['success' => false, 'message' => 'Order not found'], 404);
            }

            // Check for duplicate events
            $existingEvent = PaymentEvent::where('idempotency_key', 'vipps_webhook_'.$reference.'_'.$event)
                ->first();

            if ($existingEvent) {
                return response()->json(['success' => true, 'message' => 'Event already processed']);
            }

            // Process webhook event
            $this->processWebhookEvent($order, $event, $request->all());

            // Log payment event
            PaymentEvent::create([
                'idempotency_key' => 'vipps_webhook_'.$reference.'_'.$event,
                'provider' => 'vipps',
                'type' => 'webhook_'.$event,
                'payload' => $request->all(),
                'status' => 'processed',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Webhook processed successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Vipps webhook processing failed', [
                'reference' => $reference,
                'event' => $event,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Webhook processing failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Capture payment.
     */
    public function capturePayment(Request $request)
    {
        if (! $this->isVippsConfigured()) {
            return $this->vippsNotConfiguredResponse();
        }

        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $order = Order::findOrFail($request->order_id);

        if ($order->payment_provider !== 'vipps') {
            return response()->json([
                'success' => false,
                'message' => 'Order is not a Vipps payment',
            ], 400);
        }

        try {
            $accessToken = $this->getAccessToken();
            $reference = $order->payment_provider_ref;

            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$accessToken,
                'Ocp-Apim-Subscription-Key' => $this->subscriptionKey,
                'Merchant-Serial-Number' => $this->merchantSerialNumber,
            ])->post($this->baseUrl."/epayment/v1/payments/{$reference}/capture");

            if (! $response->successful()) {
                throw new \Exception('Vipps capture error: '.$response->body());
            }

            $captureResponse = $response->json();

            // Update order
            $order->update([
                'payment_status' => 'paid',
                'status' => 'confirmed',
                'payment_meta' => array_merge($order->payment_meta ?? [], [
                    'captured_at' => now()->toISOString(),
                    'capture_response' => $captureResponse,
                ]),
            ]);

            // Dispatch OrderPaid event to trigger task generation
            OrderPaid::dispatch($order);

            return response()->json([
                'success' => true,
                'data' => $captureResponse,
                'message' => 'Payment captured successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Vipps payment capture failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to capture payment',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Refund payment.
     */
    public function refundPayment(Request $request)
    {
        if (! $this->isVippsConfigured()) {
            return $this->vippsNotConfiguredResponse();
        }

        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
            'amount' => 'nullable|numeric|min:0',
            'reason' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $order = Order::findOrFail($request->order_id);

        if ($order->payment_provider !== 'vipps') {
            return response()->json([
                'success' => false,
                'message' => 'Order is not a Vipps payment',
            ], 400);
        }

        try {
            $accessToken = $this->getAccessToken();
            $reference = $order->payment_provider_ref;
            $amount = $request->amount ?? $order->total_amount;

            $refundData = [
                'amount' => [
                    'currency' => 'NOK',
                    'value' => $amount * 100, // Convert to øre
                ],
                'description' => $request->reason ?? 'Refund for order #'.$order->order_number,
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$accessToken,
                'Ocp-Apim-Subscription-Key' => $this->subscriptionKey,
                'Merchant-Serial-Number' => $this->merchantSerialNumber,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl."/epayment/v1/payments/{$reference}/refund", $refundData);

            if (! $response->successful()) {
                throw new \Exception('Vipps refund error: '.$response->body());
            }

            $refundResponse = $response->json();

            // Update order
            $order->update([
                'payment_status' => 'refunded',
                'payment_meta' => array_merge($order->payment_meta ?? [], [
                    'refunded_at' => now()->toISOString(),
                    'refund_amount' => $amount,
                    'refund_response' => $refundResponse,
                ]),
            ]);

            return response()->json([
                'success' => true,
                'data' => $refundResponse,
                'message' => 'Payment refunded successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Vipps payment refund failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to refund payment',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get access token from Vipps.
     */
    private function getAccessToken(): string
    {
        $response = Http::asForm()->post($this->baseUrl.'/accessToken/get', [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'Ocp-Apim-Subscription-Key' => $this->subscriptionKey,
        ]);

        if (! $response->successful()) {
            throw new \Exception('Failed to get Vipps access token: '.$response->body());
        }

        return $response->json()['access_token'];
    }

    /**
     * Get payment status from Vipps.
     */
    private function getPaymentStatus(string $reference): array
    {
        $accessToken = $this->getAccessToken();

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$accessToken,
            'Ocp-Apim-Subscription-Key' => $this->subscriptionKey,
            'Merchant-Serial-Number' => $this->merchantSerialNumber,
        ])->get($this->baseUrl."/epayment/v1/payments/{$reference}");

        if (! $response->successful()) {
            throw new \Exception('Failed to get payment status: '.$response->body());
        }

        return $response->json();
    }

    /**
     * Update order payment status based on Vipps response.
     */
    private function updateOrderPaymentStatus(Order $order, array $paymentStatus): void
    {
        $state = $paymentStatus['state'] ?? 'UNKNOWN';

        $paymentStatusMap = [
            'CREATED' => 'pending',
            'AUTHORIZED' => 'pending',
            'TERMINATED' => 'cancelled',
            'CANCELLED' => 'cancelled',
            'CAPTURED' => 'paid',
            'PARTIALLY_CAPTURED' => 'paid',
            'REFUNDED' => 'refunded',
            'PARTIALLY_REFUNDED' => 'refunded',
        ];

        $newStatus = $paymentStatusMap[$state] ?? 'pending';

        $order->update([
            'payment_status' => $newStatus,
            'payment_meta' => array_merge($order->payment_meta ?? [], [
                'vipps_state' => $state,
                'last_updated' => now()->toISOString(),
                'payment_details' => $paymentStatus,
            ]),
        ]);
    }

    /**
     * Process webhook event.
     */
    private function processWebhookEvent(Order $order, string $event, array $data): void
    {
        switch ($event) {
            case 'payment.authorized':
                $order->update(['payment_status' => 'pending']);
                break;

            case 'payment.captured':
                $order->update(['payment_status' => 'paid', 'status' => 'confirmed']);
                // Dispatch OrderPaid event to trigger task generation
                OrderPaid::dispatch($order);
                break;

            case 'payment.terminated':
            case 'payment.cancelled':
                $order->update(['payment_status' => 'cancelled']);
                break;

            case 'payment.refunded':
                $order->update(['payment_status' => 'refunded']);
                break;
        }
    }

    /**
     * Check if Vipps is properly configured.
     */
    private function isVippsConfigured(): bool
    {
        return ! empty($this->clientId) &&
               ! empty($this->clientSecret) &&
               ! empty($this->subscriptionKey) &&
               ! empty($this->merchantSerialNumber);
    }

    public function handleConsentRemoval(Request $request)
    {
        Log::info('Vipps consent removal callback received', [
            'payload' => $request->all(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Consent removal acknowledged',
        ]);
    }

    public function getShippingDetails(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => [
                'shippingDetails' => [
                    [
                        'isDefault' => true,
                        'priority' => 1,
                        'shippingMethod' => 'Standard delivery',
                        'shippingMethodId' => 'standard',
                        'fixedPrice' => 0,
                        'variablePrice' => 0,
                        'shippingMethodDescription' => 'Standard delivery',
                    ],
                ],
            ],
        ]);
    }

    public function vippsFallback(Request $request, int $orderId)
    {
        return redirect('/account/orders/'.$orderId.'?payment_provider=vipps&payment_return=1');
    }

    private function vippsNotConfiguredResponse()
    {
        return response()->json([
            'success' => false,
            'message' => 'Vipps is not configured',
            'required_config' => [
                'VIPPS_CLIENT_ID',
                'VIPPS_CLIENT_SECRET',
                'VIPPS_SUBSCRIPTION_KEY',
                'VIPPS_MERCHANT_SERIAL_NUMBER',
            ],
        ], 503);
    }
}
