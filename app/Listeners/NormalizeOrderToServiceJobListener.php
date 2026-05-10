<?php

namespace App\Listeners;

use App\Domain\Operations\Actions\NormalizeOrderToServiceJobAction;
use App\Events\OrderPlaced;

class NormalizeOrderToServiceJobListener
{
    public function __construct(private readonly NormalizeOrderToServiceJobAction $action) {}

    public function handle(OrderPlaced $event): void
    {
        $this->action->execute($event->order);
    }
}

