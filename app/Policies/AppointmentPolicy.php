<?php

namespace App\Policies;

use App\Models\Appointment;
use App\Models\User;

class AppointmentPolicy
{
    /**
     * View any appointment list.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'manager', 'member']);
    }

    /**
     * View a specific appointment.
     */
    public function view(User $user, Appointment $appointment): bool
    {
        if (in_array($user->role, ['admin', 'manager'])) {
            return true;
        }

        // Creator or attendee can view
        return $appointment->created_by === $user->id ||
               $appointment->attendees->contains($user->id);
    }

    /**
     * Create a new appointment.
     */
    public function create(User $user): bool
    {
        return in_array($user->role, ['admin', 'manager', 'member']);
    }

    /**
     * Update an appointment.
     */
    public function update(User $user, Appointment $appointment): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        // Managers or creator can edit
        return $appointment->created_by === $user->id;
    }

    /**
     * Delete an appointment.
     */
    public function delete(User $user, Appointment $appointment): bool
    {
        return $user->role === 'admin' || $appointment->created_by === $user->id;
    }
}
