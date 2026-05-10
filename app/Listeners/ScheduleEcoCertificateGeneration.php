<?php

namespace App\Listeners;

use App\Events\OrderCompleted;

class ScheduleEcoCertificateGeneration
{
    /**
     * Handle the event.
     *
     * @todo Implement actual EcoCertificate generation for ECO_DISPOSAL orders
     */
    public function handle(OrderCompleted $event): void
    {
        $order = $event->order;

        if (! $order->isEcoDisposal()) {
            return;
        }

        // TODO: when implementing eco certificates:
        // - dispatch job to generate EcoCertificate
        // - link resulting certificate to $order->ecoCertificate()
    }
}
