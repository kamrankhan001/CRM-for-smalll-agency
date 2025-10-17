<?php

namespace App\Actions\Document;

use App\Concerns\HasMorphTypes;
use App\Models\Document;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class CreateDocumentAction
{
    use HasMorphTypes;

    public function execute(array $data, UploadedFile $file, User $currentUser): void
    {
        DB::transaction(function () use ($data, $file, $currentUser) {
            $data['documentable_type'] = $this->mapMorphType($data['documentable_type']);
            $data['uploaded_by'] = $currentUser->id;
            $data['file_path'] = $file->store('documents', 'public');

            Document::create($data);
        });
    }
}
