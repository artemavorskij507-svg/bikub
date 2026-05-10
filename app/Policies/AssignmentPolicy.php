<?php

namespace App\Policies;

use App\Domain\Dispatch\Models\Assignment;
use App\Models\User;

class AssignmentPolicy
{
    public function update(User $user, Assignment $assignment): bool
    {
        return $user->id === $assignment->executor?->user_id
            || $user->hasRole('admin')
            || $user->hasPermission('ops.assignments.update')
            || $user->can('ops.assignments.update');
    }
}
