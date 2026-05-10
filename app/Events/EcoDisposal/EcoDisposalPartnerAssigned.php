<?php

namespace App\Events\EcoDisposal;

use App\Models\DisposalPartner;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EcoDisposalPartnerAssigned
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Order $order,
        public DisposalPartner $partner,
        public ?User $dispatcher = null
    ) {}
}
