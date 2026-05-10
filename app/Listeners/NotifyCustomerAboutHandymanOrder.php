<?php

namespace App\Listeners;

use App\Events\HandymanOrderRequested;
use App\Notifications\HandymanOrderCreatedForCustomer;

class NotifyCustomerAboutHandymanOrder
{
    public function handle(HandymanOrderRequested $event): void
    {
        $order = $event->order->load('user');

        if (! $order->user) {
            return;
        }

        $order->user->notify(new HandymanOrderCreatedForCustomer($order));
    }
}
