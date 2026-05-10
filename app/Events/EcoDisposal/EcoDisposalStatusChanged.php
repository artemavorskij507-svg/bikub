<?php

namespace App\Events\EcoDisposal;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EcoDisposalStatusChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Order $order,
        public ?string $from,
        public string $to,
        public ?User $dispatcher = null
    ) {}
}
