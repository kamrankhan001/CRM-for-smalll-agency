<?php

namespace App\Policies;

use App\Models\Activity;
use App\Models\User;

class ActivityPolicy
{
    /**
     * Determine whether the user can view any activities.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'manager', 'member']);
    }

    /**
     * Determine whether the user can view the activity.
     */
    public function view(User $user, Activity $activity): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'manager') {
            return $activity->causer?->role !== 'admin';
        }

        if ($user->role === 'member') {
            return $activity->causer_id === $user->id;
        }

        return false;
    }

    /**
     * Disable manual create/update/delete of activity logs.
     */
    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Activity $activity): bool
    {
        return false;
    }

    public function delete(User $user, Activity $activity): bool
    {
        return $user->role === 'admin';
    }
}
