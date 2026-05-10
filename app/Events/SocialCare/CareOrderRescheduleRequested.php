<?php

namespace App\Events\SocialCare;

use App\Models\CareOrderChangeRequest;
use App\Models\Order;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CareOrderRescheduleRequested
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Order $order,
        public CareOrderChangeRequest $changeRequest,
    ) {}
}
