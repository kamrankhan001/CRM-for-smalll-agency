<?php

namespace App\Actions\Note;

use App\Concerns\HasMorphTypes;
use App\Models\Note;
use Illuminate\Support\Facades\DB;

class UpdateNoteAction
{
    use HasMorphTypes;

    public function execute(Note $note, array $data): void
    {
        DB::transaction(function () use ($note, $data) {
            $data['noteable_type'] = $this->mapMorphType($data['noteable_type']);
            $note->update($data);
        });
    }
}
