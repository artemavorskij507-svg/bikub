<?php

namespace App\Events;

use App\Models\Claim;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ClaimCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Claim $claim
    ) {}
}
