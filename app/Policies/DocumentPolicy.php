<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;

class DocumentPolicy
{
    /**
     * Determine whether the user can view any documents.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'manager', 'member']);
    }

    /**
     * Determine whether the user can view a specific document.
     */
    public function view(User $user, Document $document): bool
    {
        if (in_array($user->role, ['admin', 'manager'])) {
            return true;
        }

        // member: can view if they uploaded it
        return $document->uploaded_by === $user->id;
    }

    /**
     * Determine whether the user can create documents.
     */
    public function create(User $user): bool
    {
        return in_array($user->role, ['admin', 'manager', 'member']);
    }

    /**
     * Determine whether the user can update a document.
     */
    public function update(User $user, Document $document): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        // manager/member: can update only their own uploads
        return $document->uploaded_by === $user->id;
    }

    /**
     * Determine whether the user can delete a document.
     */
    public function delete(User $user, Document $document): bool
    {
        return $user->role === 'admin' || $document->uploaded_by === $user->id;
    }
}
