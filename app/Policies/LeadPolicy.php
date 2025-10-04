<?php

namespace App\Policies;

use App\Models\Lead;
use App\Models\User;

class LeadPolicy
{
    /**
     * Determine whether the user can view any leads.
     */
    public function viewAny(User $user): bool
    {
        // All roles can view leads
        return in_array($user->role, ['admin', 'manager', 'member']);
    }

    /**
     * Determine whether the user can view a specific lead.
     */
    public function view(User $user, Lead $lead): bool
    {
        return $user->role === 'admin'
            || $user->role === 'manager'
            || $lead->created_by === $user->id
            || $lead->assigned_to === $user->id;
    }

    /**
     * Determine whether the user can create leads.
     */
    public function create(User $user): bool
    {
        // All roles can create leads
        return in_array($user->role, ['admin', 'manager', 'member']);
    }

    /**
     * Determine whether the user can update the lead.
     */
    public function update(User $user, Lead $lead): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'manager') {
            // Managers can edit any lead (but not delete)
            return true;
        }

        // Members can only edit their own or assigned leads
        return $lead->created_by === $user->id || $lead->assigned_to === $user->id;
    }

    /**
     * Determine whether the user can delete the lead.
     */
    public function delete(User $user, Lead $lead): bool
    {
        // Only admin can delete leads
        return $user->role === 'admin';
    }
}
