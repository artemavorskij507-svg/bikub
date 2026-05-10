<?php

namespace App\Events;

use App\Models\HandymanAssignment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class HandymanAssignmentStatusChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public HandymanAssignment $assignment,
        public string $oldStatus,
        public string $newStatus
    ) {}
}
