<?php

namespace App\Policies;

use App\Domain\Dispatch\Models\ExecutorBreak;
use App\Models\User;

class ExecutorBreakPolicy
{
    private function allowed(User $user, string $permission): bool
    {
        return $user->hasRole('admin') || $user->hasPermission($permission);
    }

    public function viewAny(User $user): bool { return $this->allowed($user, 'ops.breaks.viewAny'); }
    public function view(User $user, ExecutorBreak $break): bool { return $this->viewAny($user); }
    public function create(User $user): bool { return $this->allowed($user, 'ops.breaks.create'); }
    public function update(User $user, ExecutorBreak $break): bool { return $this->allowed($user, 'ops.breaks.update'); }
    public function delete(User $user, ExecutorBreak $break): bool { return $this->allowed($user, 'ops.breaks.delete'); }
}
