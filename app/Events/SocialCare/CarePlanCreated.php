<?php

namespace App\Events\SocialCare;

use App\Models\CarePlan;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CarePlanCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public CarePlan $carePlan,
        public ?User $initiator = null,
    ) {}
}
