<?php

namespace App\Events;

use App\Models\ClaimMessage;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ClaimMessageCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public ClaimMessage $message
    ) {}
}
