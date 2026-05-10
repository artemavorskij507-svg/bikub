<?php

namespace App\Services\Payments;

use App\Events\PaymentStatusChanged;
use App\Models\Order;
use App\Models\PaymentEvent;
use App\Models\PaymentTransaction;
use App\Services\Payments\Gateways\ManualPaymentGateway;
use App\Services\Payments\Gateways\MockVippsMobilePayGateway;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentEngine
{
    public function reserve(Order $order, string $gateway = 'manual', array $payload = []): array
    {
        return $this->runGatewayAction($order, $gateway, 'reserve', $payload, 'reserved');
    }

    public function capture(Order $order, string $gateway = 'manual', array $payload = []): array
    {
        return $this->runGatewayAction($order, $gateway, 'capture', $payload, 'captured');
    }

    public function refund(Order $order, string $gateway = 'manual', array $payload = []): array
    {
        return $this->runGatewayAction($order, $gateway, 'refund', $payload, 'refunded');
    }

    private function runGatewayAction(Order $order, string $gatewayName, string $action, array $payload, string $orderPaymentStatus): array
    {
        $gateway = $this->gateway($gatewayName);
        $result = $gateway->{$action}($order, $payload);

        DB::transaction(function () use ($order, $result, $action, $orderPaymentStatus): void {
            $order->payment_status = $orderPaymentStatus;
            $order->save();
            event(new PaymentStatusChanged($order, $orderPaymentStatus));

            PaymentTransaction::create([
                'user_id' => $order->user_id,
                'order_id' => $order->id,
                'type' => $action,
                'currency' => $order->currency ?? 'NOK',
                'amount_minor' => (int) round(((float) ($order->total_amount ?? 0)) * 100),
                'provider' => (string) ($result['provider'] ?? 'manual'),
                'provider_payment_id' => $result['reference'] ?? null,
                'status' => $orderPaymentStatus,
                'label' => 'payment_'.$action,
                'meta' => $result,
                'processed_at' => now(),
            ]);

            PaymentEvent::create([
                'id' => (string) Str::uuid(),
                'idempotency_key' => 'order:'.$order->id.':'.$action.':'.now()->timestamp,
                'provider' => (string) ($result['provider'] ?? 'manual'),
                'type' => 'payment_'.$action,
                'payload' => [
                    'order_id' => $order->id,
                    'result' => $result,
                ],
                'status' => 'processed',
            ]);
        });

        return $result;
    }

    private function gateway(string $name): PaymentGatewayInterface
    {
        return match ($name) {
            'mock_vipps_mobilepay' => new MockVippsMobilePayGateway,
            default => new ManualPaymentGateway,
        };
    }
}
