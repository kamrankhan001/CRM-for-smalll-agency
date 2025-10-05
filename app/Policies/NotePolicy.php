<?php

namespace App\Policies;

use App\Models\Note;
use App\Models\User;

class NotePolicy
{
    public function viewAny(User $user)
    {
        return true;
    }

    public function view(User $user, Note $note)
    {
        return true;
    }

    public function create(User $user)
    {
        return in_array($user->role, ['admin', 'manager', 'member']);
    }

    public function update(User $user, Note $note)
    {
        return $user->role === 'admin' || $note->user_id === $user->id;
    }

    public function delete(User $user, Note $note)
    {
        return $user->role === 'admin' || $note->user_id === $user->id;
    }
}
