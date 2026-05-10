<?php

namespace App\Domain\Dispatch\Actions;

use App\Domain\Dispatch\Exceptions\DispatchConflictException;
use App\Models\Operations\Assignment;

class GuardAssignmentMutableAction
{
    public function execute(?Assignment $assignment): void
    {
        if (! $assignment) {
            return;
        }

        if (in_array((string) $assignment->status, ['completed', 'cancelled'], true)) {
            throw new DispatchConflictException('assignment_not_mutable');
        }
    }
}

