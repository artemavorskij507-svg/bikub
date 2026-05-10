<?php

namespace App\Listeners;

use App\Events\OrderPlaced;
use Illuminate\Queue\InteractsWithQueue;

class LogOrderActivity
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(OrderPlaced $event): void
    {
        $order = $event->order;
        $user = $order->user;

        if (! $user) {
            return;
        }

        // Log order placement
        activity()
            ->performedOn($order)
            ->by($user)
            ->event('order_placed')
            ->withProperties([
                'order_number' => $order->order_number,
                'amount' => $order->total_amount,
                'service_type' => $order->service_type,
            ])
            ->log('Order placed successfully.');
    }
}
