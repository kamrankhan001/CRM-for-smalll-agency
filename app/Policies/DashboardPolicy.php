<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DashboardPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if the user can view the dashboard.
     */
    public function view(User $user): bool
    {
        // All authenticated users can view the dashboard
        return true;
    }

    /**
     * Determine if the user can view admin statistics.
     */
    public function viewAdminStats(User $user): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Determine if the user can view manager statistics.
     */
    public function viewManagerStats(User $user): bool
    {
        return in_array($user->role, ['admin', 'manager']);
    }

    /**
     * Determine if the user can view user management data.
     */
    public function viewUserManagement(User $user): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Determine if the user can view invoice statistics.
     */
    public function viewInvoiceStats(User $user): bool
    {
        return in_array($user->role, ['admin', 'manager']);
    }

    /**
     * Determine if the user can view team performance data.
     */
    public function viewTeamPerformance(User $user): bool
    {
        return in_array($user->role, ['admin', 'manager']);
    }

    /**
     * Determine if the user can view all leads.
     */
    public function viewAllLeads(User $user): bool
    {
        return in_array($user->role, ['admin', 'manager']);
    }

    /**
     * Determine if the user can view all tasks.
     */
    public function viewAllTasks(User $user): bool
    {
        return in_array($user->role, ['admin', 'manager']);
    }

    /**
     * Determine if the user can view all clients.
     */
    public function viewAllClients(User $user): bool
    {
        return in_array($user->role, ['admin', 'manager']);
    }

    /**
     * Determine if the user can view all appointments.
     */
    public function viewAllAppointments(User $user): bool
    {
        return in_array($user->role, ['admin', 'manager']);
    }

    /**
     * Scope queries for the dashboard based on user role.
     */
    public function scopeDashboardData(User $user, $model)
    {
        if ($user->role === 'admin') {
            return $model->newQuery();
        }

        if ($user->role === 'manager') {
            // Managers can see all team data but not user management
            if ($model instanceof User) {
                return $model->where('id', $user->id); // Only see themselves
            }

            return $model->newQuery();
        }

        // Regular members can only see their own data
        if ($model instanceof \App\Models\Lead) {
            return $model->where('created_by', $user->id);
        }

        if ($model instanceof \App\Models\Task) {
            return $model->where('assigned_to', $user->id)
                ->orWhere('created_by', $user->id);
        }

        if ($model instanceof \App\Models\Appointment) {
            return $model->where('created_by', $user->id)
                ->orWhereHas('attendees', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                });
        }

        if ($model instanceof \App\Models\Note) {
            return $model->where('user_id', $user->id);
        }

        if ($model instanceof \App\Models\Project) {
            // Use the many-to-many relationship through project_user
            return $model->where('created_by', $user->id)
                ->orWhereHas('members', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                });
        }

        if ($model instanceof \App\Models\Client) {
            return $model->where('created_by', $user->id);
        }

        if ($model instanceof \App\Models\Document) {
            return $model->where('uploaded_by', $user->id);
        }

        // For other models, only show user's own data
        if (method_exists($model, 'where')) {
            return $model->where('created_by', $user->id);
        }

        return $model->newQuery();
    }
}
