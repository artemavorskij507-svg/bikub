<?php

namespace App\Services\Payouts;

use App\Events\PayoutCreated;
use App\Models\Order;
use App\Models\Payout;

class PayoutEngine
{
    public function createCandidate(Order $order, int $userId, string $type = 'worker'): ?Payout
    {
        if (! in_array($order->status, ['completed', 'client_confirmed'], true)) {
            return null;
        }

        $payout = Payout::firstOrCreate(
            ['order_id' => $order->id, 'user_id' => $userId],
            [
                'type' => $type,
                'status' => 'pending',
                'amount' => (float) ($order->final_price ?? $order->total_amount ?? 0),
                'currency' => $order->currency ?? 'NOK',
                'meta' => ['source' => 'payout_engine'],
            ]
        );

        event(new PayoutCreated($payout));

        return $payout;
    }
}
