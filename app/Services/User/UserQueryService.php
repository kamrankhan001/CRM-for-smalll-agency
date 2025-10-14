<?php

namespace App\Services\User;

use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class UserQueryService
{
    public function getFilteredUsers(array $filters): LengthAwarePaginator
    {
        $query = User::query();

        // Apply search filter
        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")
                  ->orWhere('email', 'like', "%{$filters['search']}%");
            });
        }

        // Apply role filter
        if (!empty($filters['role'])) {
            $query->where('role', $filters['role']);
        }

        // Apply date range filters
        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->latest()->paginate(10);
    }

    public function transformUsersForResponse(LengthAwarePaginator $users): array
    {
        $transformedUsers = $users->through(fn ($user) => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'created_at' => $user->created_at->toDateString(),
        ]);

        return [
            'data' => $transformedUsers->items(),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
                'prev_page_url' => $users->previousPageUrl(),
                'next_page_url' => $users->nextPageUrl(),
            ],
            'links' => $users->linkCollection()->toArray(),
        ];
    }

    public function getUserWithRelations(User $user): array
    {
        $user->load([
            'assignedClients',
            'assignedLeads',
            'tasks' => function ($query) {
                $query->latest()->limit(5);
            },
            'projects' => function ($query) {
                $query->orderBy('projects.created_at', 'desc')->limit(5);
            },
            'ownedProjects' => function ($query) {
                $query->latest()->limit(5);
            },
            'uploadedDocuments' => function ($query) {
                $query->latest()->limit(5);
            },
        ]);

        return [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'created_at' => $user->created_at->toISOString(),
                'updated_at' => $user->updated_at->toISOString(),
            ],
            'stats' => [
                'assigned_clients_count' => $user->assignedClients->count(),
                'assigned_leads_count' => $user->assignedLeads->count(),
                'tasks_count' => $user->tasks->count(),
                'projects_count' => $user->projects->count(),
                'owned_projects_count' => $user->ownedProjects->count(),
                'uploaded_documents_count' => $user->uploadedDocuments->count(),
            ],
            'assigned_clients' => $user->assignedClients->map(fn ($client) => [
                'id' => $client->id,
                'name' => $client->name,
                'company' => $client->company,
                'status' => $client->status,
                'created_at' => $client->created_at->toISOString(),
            ]),
            'assigned_leads' => $user->assignedLeads->map(fn ($lead) => [
                'id' => $lead->id,
                'name' => $lead->name,
                'company' => $lead->company,
                'status' => $lead->status,
                'created_at' => $lead->created_at->toISOString(),
            ]),
            'recent_tasks' => $user->tasks->map(fn ($task) => [
                'id' => $task->id,
                'title' => $task->title,
                'status' => $task->status,
                'priority' => $task->priority,
                'due_date' => $task->due_date?->toISOString(),
                'created_at' => $task->created_at->toISOString(),
            ]),
            'recent_projects' => $user->projects->map(fn ($project) => [
                'id' => $project->id,
                'name' => $project->name,
                'status' => $project->status,
                'client_id' => $project->client_id,
                'created_at' => $project->created_at->toISOString(),
            ]),
            'owned_projects' => $user->ownedProjects->map(fn ($project) => [
                'id' => $project->id,
                'name' => $project->name,
                'status' => $project->status,
                'client_id' => $project->client_id,
                'created_at' => $project->created_at->toISOString(),
            ]),
            'recent_documents' => $user->uploadedDocuments->map(fn ($document) => [
                'id' => $document->id,
                'title' => $document->title,
                'type' => $document->type,
                'created_at' => $document->created_at->toISOString(),
            ]),
        ];
    }

    public function getUserForEdit(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
        ];
    }
}