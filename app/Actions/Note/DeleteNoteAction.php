<?php

namespace App\Actions\Note;

use App\Models\Note;
use Illuminate\Support\Facades\DB;

class DeleteNoteAction
{
    public function execute(Note $note): void
    {
        DB::transaction(function () use ($note) {
            $note->delete();
        });
    }
}