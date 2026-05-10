<?php

namespace App\Events\EcoDisposal;

use App\Models\EcoTeam;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EcoDisposalTeamAssigned
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Order $order,
        public EcoTeam $team,
        public ?User $dispatcher = null
    ) {}
}
