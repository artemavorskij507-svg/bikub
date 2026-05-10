<?php

namespace App\Events;

use App\Models\RepairUpdate;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RepairUpdateCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public RepairUpdate $update
    ) {}
}
