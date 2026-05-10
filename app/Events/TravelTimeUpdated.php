<?php

namespace App\Events;

use App\Models\TravelTime;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TravelTimeUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public TravelTime $travelTime
    ) {}
}
