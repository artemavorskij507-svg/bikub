<?php

namespace App\Domain\Exceptions\Events;

use App\Domain\Exceptions\Models\OperationException;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OperationExceptionOpened
{
    use Dispatchable, SerializesModels;

    public function __construct(public OperationException $exception) {}
}

