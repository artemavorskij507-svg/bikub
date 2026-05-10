<?php

namespace App\Observers;

use App\Models\Order;

class OrderObserver
{
    /**
     * Додати бали лояльності при завершенні замовлення
     */
    public function updated(Order $order): void
    {
        // Check if order status changed to completed
        if ($order->isDirty('status') && $order->status === 'completed' && $order->user_id) {
            $this->awardLoyaltyPoints($order);
        }
    }

    /**
     * Надати бали за замовлення
     */
    private function awardLoyaltyPoints(Order $order): void
    {
        if (! $order->user || ! $order->total_amount) {
            return;
        }

        // Calculate points: 1 point per 1 UAH
        $points = (int) floor($order->total_amount);

        if ($points <= 0) {
            return;
        }

        $balance = $order->user->getOrCreateLoyaltyBalance();

        $balance->addPoints(
            $points,
            "Бали за замовлення #{$order->order_number}",
            'Order',
            $order->id
        );
    }

    /**
     * Handle the Order "created" event.
     */
    public function created(Order $order): void
    {
        //
    }

    /**
     * Handle the Order "deleted" event.
     */
    public function deleted(Order $order): void
    {
        //
    }

    /**
     * Handle the Order "restored" event.
     */
    public function restored(Order $order): void
    {
        //
    }

    /**
     * Handle the Order "force deleted" event.
     */
    public function forceDeleted(Order $order): void
    {
        //
    }
}
