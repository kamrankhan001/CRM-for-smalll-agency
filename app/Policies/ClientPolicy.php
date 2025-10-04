<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\User;

class ClientPolicy
{
    /**
     * Determine whether the user can view any clients.
     */
    public function viewAny(User $user): bool
    {
        // All roles can view clients
        return in_array($user->role, ['admin', 'manager', 'member']);
    }

    /**
     * Determine whether the user can view a specific client.
     */
    public function view(User $user, Client $client): bool
    {
        return $user->role === 'admin'
            || $user->role === 'manager'
            || $client->created_by === $user->id
            || $client->assigned_to === $user->id;
    }

    /**
     * Determine whether the user can create clients.
     */
    public function create(User $user): bool
    {
        // All roles can create clients
        return in_array($user->role, ['admin', 'manager', 'member']);
    }

    /**
     * Determine whether the user can update the client.
     */
    public function update(User $user, Client $client): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'manager') {
            // Managers can edit any client (but not delete)
            return true;
        }

        // Members can only edit their own or assigned clients
        return $client->created_by === $user->id || $client->assigned_to === $user->id;
    }

    /**
     * Determine whether the user can delete the client.
     */
    public function delete(User $user, Client $client): bool
    {
        // Only admin can delete clients
        return $user->role === 'admin';
    }
}
