<?php

namespace App\Actions\Note;

use App\Concerns\HasMorphTypes;
use App\Models\Note;
use App\Models\User;
use App\Notifications\NoteAddedNotification;
use Illuminate\Support\Facades\DB;

class CreateNoteAction
{
    use HasMorphTypes;

    public function execute(array $data, User $currentUser): void
    {
        DB::transaction(function () use ($data, $currentUser) {
            $data['noteable_type'] = $this->mapMorphType($data['noteable_type']);
            $data['user_id'] = $currentUser->id;

            $note = Note::create($data);

            // Send notification to ALL users in the system
            $allUsers = User::all();
            foreach ($allUsers as $user) {
                $user->notify(new NoteAddedNotification($note, $currentUser));
            }
        });
    }
}