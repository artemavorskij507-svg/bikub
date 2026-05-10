<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VehicleInspectionPreset;
use Illuminate\Auth\Access\HandlesAuthorization;

class VehicleInspectionPresetPolicy
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

    public function view(?User $user, VehicleInspectionPreset $model): bool
    {
        return (bool) $user;
    }

    public function create(?User $user): bool
    {
        return (bool) $user;
    }

    public function update(?User $user, VehicleInspectionPreset $model): bool
    {
        return (bool) $user;
    }

    public function delete(?User $user, VehicleInspectionPreset $model): bool
    {
        return (bool) $user;
    }
}
