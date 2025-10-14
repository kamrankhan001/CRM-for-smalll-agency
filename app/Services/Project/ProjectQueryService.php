<?php

namespace App\Services\Project;

use App\Models\Project;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class ProjectQueryService
{
    public function getFilteredProjects(array $filters, User $currentUser): LengthAwarePaginator
    {
        $query = Project::with(['client', 'lead', 'creator', 'members']);

        // Apply search filter
        if (!empty($filters['search'])) {
            $query->where('name', 'like', "%{$filters['search']}%");
        }

        // Apply status filter
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Apply client filter
        if (!empty($filters['client_id'])) {
            $query->where('client_id', $filters['client_id']);
        }

        // Apply lead filter
        if (!empty($filters['lead_id'])) {
            $query->where('lead_id', $filters['lead_id']);
        }

        // Apply created_by filter
        if (!empty($filters['created_by'])) {
            $query->where('created_by', $filters['created_by']);
        }

        // Apply date range filters
        if (!empty($filters['date_from'])) {
            $query->whereDate('start_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('end_date', '<=', $filters['date_to']);
        }

        // Apply role-based restrictions for members
        if ($currentUser->role === 'member') {
            $query->where(function ($q) use ($currentUser) {
                $q->where('created_by', $currentUser->id)
                  ->orWhereHas('members', function ($memberQuery) use ($currentUser) {
                      $memberQuery->where('user_id', $currentUser->id);
                  });
            });
        }

        return $query->latest()->paginate(10);
    }

    public function transformProjectsForResponse(LengthAwarePaginator $projects): array
    {
        $transformedProjects = $projects->through(fn ($project) => [
            'id' => $project->id,
            'name' => $project->name,
            'description' => $project->description,
            'status' => $project->status,
            'start_date' => $project->start_date?->toDateString(),
            'end_date' => $project->end_date?->toDateString(),
            'client' => $project->client ? [
                'id' => $project->client->id,
                'name' => $project->client->name,
            ] : null,
            'lead' => $project->lead ? [
                'id' => $project->lead->id,
                'name' => $project->lead->name,
            ] : null,
            'creator' => $project->creator ? [
                'id' => $project->creator->id,
                'name' => $project->creator->name,
            ] : null,
            'members' => $project->members->map(fn ($member) => [
                'id' => $member->id,
                'name' => $member->name,
            ]),
            'created_by' => $project->created_by,
            'created_at' => $project->created_at->toDateString(),
            'updated_at' => $project->updated_at->toDateString(),
        ]);

        return [
            'data' => $transformedProjects->items(),
            'meta' => [
                'current_page' => $projects->currentPage(),
                'last_page' => $projects->lastPage(),
                'per_page' => $projects->perPage(),
                'total' => $projects->total(),
                'from' => $projects->firstItem(),
                'to' => $projects->lastItem(),
                'prev_page_url' => $projects->previousPageUrl(),
                'next_page_url' => $projects->nextPageUrl(),
            ],
            'links' => $projects->linkCollection()->toArray(),
        ];
    }

    public function getProjectWithRelations(Project $project): array
    {
        $project->load([
            'client',
            'lead',
            'creator',
            'members',
            'tasks' => function ($query) {
                $query->latest()->limit(10);
            },
            'invoices' => function ($query) {
                $query->latest()->limit(10);
            },
            'notes' => function ($query) {
                $query->with('user')->latest()->limit(10);
            },
            'activities' => function ($query) {
                $query->with('causer')->latest()->limit(10);
            },
            'documents',
        ]);

        return [
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'description' => $project->description,
                'status' => $project->status,
                'start_date' => $project->start_date?->toISOString(),
                'end_date' => $project->end_date?->toISOString(),
                'budget' => $project->budget,
                'client_id' => $project->client_id,
                'lead_id' => $project->lead_id,
                'created_by' => $project->created_by,
                'created_at' => $project->created_at->toISOString(),
                'updated_at' => $project->updated_at->toISOString(),
                'client' => $project->client ? [
                    'id' => $project->client->id,
                    'name' => $project->client->name,
                    'company' => $project->client->company,
                ] : null,
                'lead' => $project->lead ? [
                    'id' => $project->lead->id,
                    'name' => $project->lead->name,
                ] : null,
                'creator' => $project->creator ? [
                    'id' => $project->creator->id,
                    'name' => $project->creator->name,
                ] : null,
                'members' => $project->members->map(fn ($member) => [
                    'id' => $member->id,
                    'name' => $member->name,
                    'email' => $member->email,
                ]),
            ],
            'tasks' => $project->tasks->map(fn ($task) => [
                'id' => $task->id,
                'title' => $task->title,
                'description' => $task->description,
                'status' => $task->status,
                'priority' => $task->priority,
                'due_date' => $task->due_date?->toISOString(),
                'assigned_to' => $task->assigned_to,
                'created_at' => $task->created_at->toISOString(),
            ]),
            'invoices' => $project->invoices->map(fn ($invoice) => [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'amount' => $invoice->amount,
                'status' => $invoice->status,
                'due_date' => $invoice->due_date?->toISOString(),
                'created_at' => $invoice->created_at->toISOString(),
            ]),
            'notes' => $project->notes->map(fn ($note) => [
                'id' => $note->id,
                'content' => $note->content,
                'user' => [
                    'id' => $note->user->id,
                    'name' => $note->user->name,
                ],
                'created_at' => $note->created_at->toISOString(),
            ]),
            'activities' => $project->activities->map(fn ($activity) => [
                'id' => $activity->id,
                'description' => $activity->description,
                'causer' => $activity->causer ? [
                    'id' => $activity->causer->id,
                    'name' => $activity->causer->name,
                ] : null,
                'created_at' => $activity->created_at->toISOString(),
                'properties' => $activity->properties,
            ]),
            'documents_count' => $project->documents->count(),
        ];
    }
}