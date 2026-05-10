<?php

namespace App\Domain\AgentOS\Events;

use App\Domain\AgentOS\Models\AgentStep;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AgentStepMarkedStale
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public AgentStep $step,
        public string $reason,
    ) {
    }
}
