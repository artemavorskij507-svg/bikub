<?php

namespace App\Services\Payments\Gateways;

use App\Models\Order;
use App\Services\Payments\PaymentGatewayInterface;

class ManualPaymentGateway implements PaymentGatewayInterface
{
    public function reserve(Order $order, array $payload = []): array
    {
        return ['status' => 'reserved', 'provider' => 'manual', 'reference' => 'manual-'.$order->id.'-'.time()];
    }

    public function capture(Order $order, array $payload = []): array
    {
        return ['status' => 'captured', 'provider' => 'manual', 'reference' => 'manual-capture-'.$order->id.'-'.time()];
    }

    public function cancel(Order $order, array $payload = []): array
    {
        return ['status' => 'cancelled', 'provider' => 'manual', 'reference' => 'manual-cancel-'.$order->id.'-'.time()];
    }

    public function refund(Order $order, array $payload = []): array
    {
        return ['status' => 'refunded', 'provider' => 'manual', 'reference' => 'manual-refund-'.$order->id.'-'.time()];
    }
}

