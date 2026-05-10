<?php

namespace App\Events;

use App\Domain\Operations\Models\ServiceJob;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ServiceJobCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(public ServiceJob $job) {}
}

