<?php

namespace App\Listeners;

use App\Domain\Exceptions\Events\OperationExceptionOpened;
use App\Events\Operations\ExceptionOpened as LegacyExceptionOpened;

class BroadcastExceptionOpenedBridge
{
    public function handle(OperationExceptionOpened $event): void
    {
        event(new LegacyExceptionOpened($event->exception));
    }
}

