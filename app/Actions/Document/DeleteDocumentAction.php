<?php

namespace App\Actions\Document;

use App\Models\Document;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DeleteDocumentAction
{
    public function execute(Document $document): void
    {
        DB::transaction(function () use ($document) {
            Storage::disk('public')->delete($document->file_path);
            $document->delete();
        });
    }
}