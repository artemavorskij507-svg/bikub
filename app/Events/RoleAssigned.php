<?php

namespace App\Events;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RoleAssigned
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public User $user,
        public Role $role
    ) {}
}
