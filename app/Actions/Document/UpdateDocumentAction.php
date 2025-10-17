<?php

namespace App\Actions\Document;

use App\Concerns\HasMorphTypes;
use App\Models\Document;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class UpdateDocumentAction
{
    use HasMorphTypes;

    public function execute(Document $document, array $data, ?UploadedFile $file = null): void
    {
        DB::transaction(function () use ($document, $data, $file) {
            $data['documentable_type'] = $this->mapMorphType($data['documentable_type']);

            if ($file) {
                Storage::disk('public')->delete($document->file_path);
                $data['file_path'] = $file->store('documents', 'public');
            }

            $document->update($data);
        });
    }
}
