<?php

namespace App\Events\SocialCare;

use App\Enums\CareOrderStatus;
use App\Models\CareOrderDetails;
use App\Models\Order;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CareOrderStatusChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Order $order,
        public CareOrderDetails $details,
        public CareOrderStatus $oldStatus,
        public CareOrderStatus $newStatus,
        public ?string $reason = null,
    ) {}
}
