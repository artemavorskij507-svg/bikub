<?php

namespace App\Services\Payments;

use App\Models\Order;

interface PaymentGatewayInterface
{
    public function reserve(Order $order, array $payload = []): array;

    public function capture(Order $order, array $payload = []): array;

    public function cancel(Order $order, array $payload = []): array;

    public function refund(Order $order, array $payload = []): array;
}

