<?php

namespace App\Listeners\Notifications;

use Illuminate\Support\Facades\Log;

class LogBusinessEvent
{
    public function handle(object $event): void
    {
        Log::info('business.event', [
            'event' => class_basename($event),
            'payload' => method_exists($event, 'toArray') ? $event->toArray() : null,
        ]);
    }
}

