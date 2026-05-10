<?php

namespace App\Domain\Operations\Events;

use App\Domain\Operations\Models\ServiceJob;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ServiceJobStatusChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public ServiceJob $job,
        public string $from,
        public string $to,
        public ?string $reason = null,
        public array $context = [],
    ) {}
}

