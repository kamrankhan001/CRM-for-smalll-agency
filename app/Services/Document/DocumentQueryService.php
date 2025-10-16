<?php

namespace App\Services\Document;

use App\Concerns\HasMorphTypes;
use App\Models\Document;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;

class DocumentQueryService
{
    use HasMorphTypes;

    public function getFilteredDocuments(array $filters): LengthAwarePaginator
    {
        $query = Document::with(['documentable', 'uploader']);

        // Apply search filter
        if (!empty($filters['search'])) {
            $query->where('title', 'like', "%{$filters['search']}%");
        }

        // Apply type filter
        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        // Apply uploaded_by filter
        if (!empty($filters['uploaded_by'])) {
            $query->where('uploaded_by', $filters['uploaded_by']);
        }

        // Apply documentable_type filter
        if (!empty($filters['documentable_type'])) {
            $query->where('documentable_type', $this->mapMorphType($filters['documentable_type']));
        }

        return $query->latest()->paginate(10);
    }

    public function transformDocumentsForResponse(LengthAwarePaginator $documents): array
    {
        $transformedDocuments = $documents->through(fn ($doc) => [
            'id' => $doc->id,
            'title' => $doc->title,
            'type' => $doc->type,
            'file_path' => Storage::url($doc->file_path),
            'uploader' => $doc->uploader?->only(['id', 'name']),
            'documentable' => $doc->documentable ? [
                'id' => $doc->documentable->id,
                'name' => $doc->documentable->name,
                'type' => class_basename($doc->documentable_type),
            ] : null,
            'uploaded_by' => $doc->uploaded_by,
            'created_at' => $doc->created_at->toDateString(),
        ]);

        return [
            'data' => $transformedDocuments->items(),
            'meta' => [
                'current_page' => $documents->currentPage(),
                'last_page' => $documents->lastPage(),
                'per_page' => $documents->perPage(),
                'total' => $documents->total(),
                'from' => $documents->firstItem(),
                'to' => $documents->lastItem(),
            ],
            'links' => $documents->linkCollection()->toArray(),
        ];
    }

    public function getDocumentWithRelations(Document $document): array
    {
        $document->load([
            'documentable',
            'uploader',
            'activities' => function ($query) {
                $query->with('causer')->latest()->limit(10);
            },
        ]);

        return [
            'document' => [
                'id' => $document->id,
                'title' => $document->title,
                'type' => $document->type,
                'file_path' => $document->file_path,
                'file_url' => Storage::url($document->file_path),
                'documentable_type' => $document->documentable_type,
                'documentable_id' => $document->documentable_id,
                'uploaded_by' => $document->uploaded_by,
                'created_at' => $document->created_at->toISOString(),
                'updated_at' => $document->updated_at->toISOString(),
                'uploader' => $document->uploader ? [
                    'id' => $document->uploader->id,
                    'name' => $document->uploader->name,
                    'email' => $document->uploader->email,
                ] : null,
                'documentable' => $document->documentable ? [
                    'id' => $document->documentable->id,
                    'name' => $document->documentable->name ?? $document->documentable->title,
                    'type' => class_basename($document->documentable_type),
                ] : null,
            ],
            'activities' => $document->activities->map(fn ($activity) => [
                'id' => $activity->id,
                'description' => $activity->description,
                'causer' => $activity->causer ? [
                    'id' => $activity->causer->id,
                    'name' => $activity->causer->name,
                ] : null,
                'created_at' => $activity->created_at->toISOString(),
                'properties' => $activity->properties,
            ]),
        ];
    }

    public function getDocumentTypes(): array
    {
        return ['proposal', 'contract', 'invoice', 'report', 'brief', 'misc'];
    }

    public function getRelatedOptions(): array
    {
        return [
            'leads' => \App\Models\Lead::select('id', 'name')->get()->toArray(),
            'clients' => \App\Models\Client::select('id', 'name')->get()->toArray(),
            'projects' => \App\Models\Project::select('id', 'name')->get()->toArray(),
        ];
    }

    public function getAvailableUsers(): array
    {
        return User::select('id', 'name')->get()->toArray();
    }
}