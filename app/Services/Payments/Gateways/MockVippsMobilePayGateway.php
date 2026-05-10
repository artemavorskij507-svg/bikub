<?php

namespace App\Services\Payments\Gateways;

use App\Models\Order;
use App\Services\Payments\PaymentGatewayInterface;

class MockVippsMobilePayGateway implements PaymentGatewayInterface
{
    public function reserve(Order $order, array $payload = []): array
    {
        return ['status' => 'reserved', 'provider' => 'mock_vipps_mobilepay', 'reference' => 'vipps-res-'.$order->id.'-'.time()];
    }

    public function capture(Order $order, array $payload = []): array
    {
        return ['status' => 'captured', 'provider' => 'mock_vipps_mobilepay', 'reference' => 'vipps-cap-'.$order->id.'-'.time()];
    }

    public function cancel(Order $order, array $payload = []): array
    {
        return ['status' => 'cancelled', 'provider' => 'mock_vipps_mobilepay', 'reference' => 'vipps-can-'.$order->id.'-'.time()];
    }

    public function refund(Order $order, array $payload = []): array
    {
        return ['status' => 'refunded', 'provider' => 'mock_vipps_mobilepay', 'reference' => 'vipps-ref-'.$order->id.'-'.time()];
    }
}

