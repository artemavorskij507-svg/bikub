<?php

namespace App\Policies;

use App\Models\Partner;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class RoadsidePartnerPolicy
{
    use HandlesAuthorization;

    public function before(?User $user, string $ability): ?bool
    {
        if (! $user) {
            return false;
        }

        if (method_exists($user, 'hasAnyRole') && $user->hasAnyRole(['admin', 'operator', 'dispatcher'])) {
            return true;
        }

        return null;
    }

    public function viewAny(?User $user): bool
    {
        return (bool) $user;
    }

    public function view(?User $user, Partner $model): bool
    {
        return (bool) $user;
    }

    public function create(?User $user): bool
    {
        return (bool) $user;
    }

    public function update(?User $user, Partner $model): bool
    {
        return (bool) $user;
    }

    public function delete(?User $user, Partner $model): bool
    {
        return (bool) $user;
    }
}
