<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin') ||
               $user->hasRole('operator') ||
               $user->hasRole('courier');
    }

    public function view(User $user, Task $task): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('courier')) {
            return $task->assignee_id === $user->id ||
                   $task->order?->user_id === $user->id;
        }

        return $user->hasRole('operator');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin') || $user->hasRole('operator');
    }

    public function update(User $user, Task $task): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('courier')) {
            return $task->assignee_id === $user->id &&
                   in_array($task->status, ['assigned', 'en_route', 'arrived', 'in_progress']);
        }

        return $user->hasRole('operator');
    }

    public function delete(User $user, Task $task): bool
    {
        return $user->hasRole('admin');
    }

    public function assign(User $user, Task $task): bool
    {
        return $user->hasRole('admin') || $user->hasRole('operator');
    }

    public function complete(User $user, Task $task): bool
    {
        if ($user->hasRole('admin') || $user->hasRole('operator')) {
            return true;
        }

        if ($user->hasRole('courier')) {
            return $task->assignee_id === $user->id;
        }

        return false;
    }
}
