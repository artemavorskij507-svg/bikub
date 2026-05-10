<?php

namespace App\Events;

use App\Models\RepairProject;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RepairProjectCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public RepairProject $project
    ) {}
}
