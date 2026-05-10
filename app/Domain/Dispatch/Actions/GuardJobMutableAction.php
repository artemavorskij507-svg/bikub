<?php

namespace App\Domain\Dispatch\Actions;

use App\Domain\Dispatch\Exceptions\DispatchConflictException;
use App\Models\Operations\ServiceJob;

class GuardJobMutableAction
{
    public function execute(ServiceJob $job): void
    {
        $terminal = ['completed', 'cancelled', 'failed'];
        if (in_array((string) $job->status, $terminal, true)) {
            throw new DispatchConflictException('job_not_mutable');
        }
    }
}

