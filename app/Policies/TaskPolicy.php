<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    /**
     * Determine whether the user can view any tasks.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'manager', 'member']);
    }

    /**
     * Determine whether the user can view a specific task.
     */
    public function view(User $user, Task $task): bool
    {
        if ($user->role === 'admin' || $user->role === 'manager') {
            return true;
        }

        // member: can view only their own or assigned tasks
        return $task->assigned_to === $user->id || $task->created_by === $user->id;
    }

    /**
     * Determine whether the user can create tasks.
     */
    public function create(User $user): bool
    {
        return in_array($user->role, ['admin', 'manager', 'member']);
    }

    /**
     * Determine whether the user can update the task.
     */
    public function update(User $user, Task $task): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'manager') {
            return $task->created_by === $user->id || $task->assigned_to === $user->id;
        }

        // member can update only their own assigned or created tasks
        return $task->assigned_to === $user->id || $task->created_by === $user->id;
    }

    /**
     * Determine whether the user can delete the task.
     */
    public function delete(User $user, Task $task): bool
    {
        return $user->role === 'admin';
    }
}
