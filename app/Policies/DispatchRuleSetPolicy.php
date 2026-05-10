<?php

namespace App\Policies;

use App\Domain\Dispatch\Models\DispatchRuleSet;
use App\Models\User;

class DispatchRuleSetPolicy
{
    private function allowed(User $user, string $permission): bool
    {
        return $user->hasRole('admin') || $user->hasPermission($permission);
    }

    public function viewAny(User $user): bool { return $this->allowed($user, 'ops.rules.viewAny'); }
    public function view(User $user, DispatchRuleSet $rule): bool { return $this->viewAny($user); }
    public function create(User $user): bool { return $this->allowed($user, 'ops.rules.create'); }
    public function update(User $user, DispatchRuleSet $rule): bool { return $this->allowed($user, 'ops.rules.update'); }
    public function delete(User $user, DispatchRuleSet $rule): bool { return $this->allowed($user, 'ops.rules.delete'); }
}
