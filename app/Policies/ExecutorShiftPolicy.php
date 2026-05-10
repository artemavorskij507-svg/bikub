<?php

namespace App\Policies;

use App\Domain\Dispatch\Models\ExecutorShift;
use App\Models\User;

class ExecutorShiftPolicy
{
    private function allowed(User $user, string $permission): bool
    {
        return $user->hasRole('admin') || $user->hasPermission($permission);
    }

    public function viewAny(User $user): bool { return $this->allowed($user, 'ops.shifts.viewAny'); }
    public function view(User $user, ExecutorShift $shift): bool { return $this->viewAny($user); }
    public function create(User $user): bool { return $this->allowed($user, 'ops.shifts.create'); }
    public function update(User $user, ExecutorShift $shift): bool { return $this->allowed($user, 'ops.shifts.update'); }
    public function delete(User $user, ExecutorShift $shift): bool { return $this->allowed($user, 'ops.shifts.delete'); }
}
