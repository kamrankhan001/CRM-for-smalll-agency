<?php

namespace App\Services\Appointment;

use App\Models\Appointment;
use App\Models\User;
use App\Notifications\AppointmentCreated;
use App\Notifications\AppointmentUpdated;

class AppointmentNotificationService
{
    public function sendAppointmentNotifications(Appointment $appointment, User $actor, string $event): void
    {
        $notifiableUsers = $this->getNotifiableUsers($appointment, $actor, $event);

        foreach ($notifiableUsers as $user) {
            if ($event === 'created') {
                $user->notify(new AppointmentCreated($appointment, $actor));
            } elseif ($event === 'updated') {
                $user->notify(new AppointmentUpdated($appointment, $actor));
            }
        }
    }

    /**
     * Get users who should receive notifications for this appointment.
     */
    public function getNotifiableUsers(Appointment $appointment, User $actor, string $event = 'created'): array
    {
        $notifiableUsers = [];

        // For appointment creation, DO NOT notify admins at all
        // For updates, admins can be notified
        $includeAdmins = ($event !== 'created');

        // Get users based on appointable type (excluding the actor)
        switch ($appointment->appointable_type) {
            case 'App\\Models\\Lead':
                $lead = $appointment->appointable;
                if ($lead && $lead->assigned_to && $lead->assigned_to !== $actor->id) {
                    $assignedUser = User::find($lead->assigned_to);
                    if ($assignedUser) {
                        // If not including admins and this user is admin, skip
                        if ($includeAdmins || $assignedUser->role !== 'admin') {
                            $notifiableUsers[$assignedUser->id] = $assignedUser;
                        }
                    }
                }
                break;

            case 'App\\Models\\Client':
                $client = $appointment->appointable;
                if ($client && $client->assigned_to && $client->assigned_to !== $actor->id) {
                    $assignedUser = User::find($client->assigned_to);
                    if ($assignedUser) {
                        // If not including admins and this user is admin, skip
                        if ($includeAdmins || $assignedUser->role !== 'admin') {
                            $notifiableUsers[$assignedUser->id] = $assignedUser;
                        }
                    }
                }
                break;

            case 'App\\Models\\Project':
                $project = $appointment->appointable;
                if ($project) {
                    $projectMembers = $project->members()->where('user_id', '!=', $actor->id)->get();
                    foreach ($projectMembers as $member) {
                        // If not including admins and this user is admin, skip
                        if ($includeAdmins || $member->role !== 'admin') {
                            $notifiableUsers[$member->id] = $member;
                        }
                    }
                }
                break;
        }

        // Only add additional admins for non-creation events
        if ($includeAdmins) {
            $admins = User::where('role', 'admin')
                ->where('id', '!=', $actor->id)
                ->whereNotIn('id', array_keys($notifiableUsers)) // Don't add if already included
                ->get();

            foreach ($admins as $admin) {
                $notifiableUsers[$admin->id] = $admin;
            }
        }

        return array_values($notifiableUsers);
    }

    /**
     * Get users for appointment reminders (includes everyone, even creator)
     */
    public function getReminderNotifiableUsers(Appointment $appointment): array
    {
        $notifiableUsers = [];

        // Include appointment creator
        if ($appointment->creator) {
            $notifiableUsers[$appointment->creator->id] = $appointment->creator;
        }

        // Get users based on appointable type (include everyone for reminders)
        switch ($appointment->appointable_type) {
            case 'App\\Models\\Lead':
                $lead = $appointment->appointable;
                if ($lead && $lead->assigned_to) {
                    $assignedUser = User::find($lead->assigned_to);
                    if ($assignedUser) {
                        $notifiableUsers[$assignedUser->id] = $assignedUser;
                    }
                }
                break;

            case 'App\\Models\\Client':
                $client = $appointment->appointable;
                if ($client && $client->assigned_to) {
                    $assignedUser = User::find($client->assigned_to);
                    if ($assignedUser) {
                        $notifiableUsers[$assignedUser->id] = $assignedUser;
                    }
                }
                break;

            case 'App\\Models\\Project':
                $project = $appointment->appointable;
                if ($project) {
                    $projectMembers = $project->members()->get();
                    foreach ($projectMembers as $member) {
                        $notifiableUsers[$member->id] = $member;
                    }
                }
                break;
        }

        // Add admins for reminders (but avoid duplicates)
        $admins = User::where('role', 'admin')
            ->whereNotIn('id', array_keys($notifiableUsers))
            ->get();

        foreach ($admins as $admin) {
            $notifiableUsers[$admin->id] = $admin;
        }

        return array_values($notifiableUsers);
    }
}
