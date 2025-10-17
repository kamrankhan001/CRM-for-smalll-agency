<?php

namespace App\Services\Note;

use App\Concerns\HasMorphTypes;
use App\Models\Note;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class NoteQueryService
{
    use HasMorphTypes;

    public function getFilteredNotes(array $filters): LengthAwarePaginator
    {
        $query = Note::with(['user', 'noteable']);

        // Apply search filter
        if (! empty($filters['search'])) {
            $query->where('content', 'like', "%{$filters['search']}%");
        }

        // Apply noteable_type filter
        if (! empty($filters['noteable_type'])) {
            $query->where('noteable_type', $this->mapMorphType($filters['noteable_type']));
        }

        // Apply user_id filter
        if (! empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        // Apply date range filters
        if (! empty($filters['date_range'])) {
            $this->applyDateRangeFilter($query, $filters);
        }

        return $query->latest()->paginate(10);
    }

    public function transformNotesForResponse(LengthAwarePaginator $notes): array
    {
        $transformedNotes = $notes->through(fn ($note) => [
            'id' => $note->id,
            'content' => $note->content,
            'user' => [
                'id' => $note->user->id,
                'name' => $note->user->name,
            ],
            'user_id' => $note->user_id,
            'noteable' => $note->noteable ? [
                'id' => $note->noteable->id,
                'name' => $note->noteable->name,
                'type' => class_basename($note->noteable_type),
            ] : null,
            'created_at' => $note->created_at->toISOString(),
            'updated_at' => $note->updated_at->toISOString(),
        ]);

        return [
            'data' => $transformedNotes->items(),
            'meta' => [
                'current_page' => $notes->currentPage(),
                'last_page' => $notes->lastPage(),
                'per_page' => $notes->perPage(),
                'total' => $notes->total(),
                'from' => $notes->firstItem(),
                'to' => $notes->lastItem(),
                'prev_page_url' => $notes->previousPageUrl(),
                'next_page_url' => $notes->nextPageUrl(),
            ],
            'links' => $notes->linkCollection()->toArray(),
        ];
    }

    public function getNoteWithRelations(Note $note): array
    {
        $note->load([
            'user',
            'noteable',
            'activities' => function ($query) {
                $query->with('causer')->latest()->limit(10);
            },
        ]);

        return [
            'note' => [
                'id' => $note->id,
                'content' => $note->content,
                'user_id' => $note->user_id,
                'noteable_type' => $note->noteable_type,
                'noteable_id' => $note->noteable_id,
                'created_at' => $note->created_at->toISOString(),
                'updated_at' => $note->updated_at->toISOString(),
                'user' => [
                    'id' => $note->user->id,
                    'name' => $note->user->name,
                    'email' => $note->user->email,
                ],
                'noteable' => $note->noteable ? [
                    'id' => $note->noteable->id,
                    'name' => $note->noteable->name ?? $note->noteable->title,
                    'type' => class_basename($note->noteable_type),
                ] : null,
            ],
            'activities' => $note->activities->map(fn ($activity) => [
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

    private function applyDateRangeFilter($query, array $filters): void
    {
        $dateRanges = [
            '7_days' => now()->subDays(7),
            '15_days' => now()->subDays(15),
            '30_days' => now()->subDays(30),
            'custom' => $filters['date_from'] ?? now()->subDays(30),
        ];

        if ($filters['date_range'] === 'custom' && ! empty($filters['date_from']) && ! empty($filters['date_to'])) {
            $query->whereBetween('created_at', [
                $filters['date_from'],
                $filters['date_to'],
            ]);
        } else {
            $query->where('created_at', '>=', $dateRanges[$filters['date_range']]);
        }
    }
}
