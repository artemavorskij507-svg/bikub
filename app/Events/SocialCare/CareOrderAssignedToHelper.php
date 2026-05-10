<?php

namespace App\Events\SocialCare;

use App\Models\CareOrderDetails;
use App\Models\Order;
use App\Models\SocialHelperProfile;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CareOrderAssignedToHelper
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Order $order,
        public CareOrderDetails $details,
        public SocialHelperProfile $helperProfile,
    ) {}
}
