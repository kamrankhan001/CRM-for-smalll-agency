<?php

namespace App\Services\Task;

use App\Models\Task;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class TaskQueryService
{
    public function getFilteredTasks(array $filters, User $currentUser): LengthAwarePaginator
    {
        $query = Task::with(['assignee', 'creator', 'taskable']);

        // Apply search filter
        if (! empty($filters['search'])) {
            $query->where('title', 'like', "%{$filters['search']}%");
        }

        // Apply status filter
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Apply assigned_to filter
        if (! empty($filters['assigned_to'])) {
            $query->where('assigned_to', $filters['assigned_to']);
        }

        // Apply date range filters
        if (! empty($filters['date_from'])) {
            $query->whereDate('due_date', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('due_date', '<=', $filters['date_to']);
        }

        // Apply role-based restrictions for members
        if ($currentUser->role === 'member') {
            $query->where(function ($q) use ($currentUser) {
                $q->where('assigned_to', $currentUser->id)
                    ->orWhere('created_by', $currentUser->id);
            });
        }

        return $query->latest()->paginate(10);
    }

    public function transformTasksForResponse(LengthAwarePaginator $tasks): array
    {
        $transformedTasks = $tasks->through(fn ($task) => [
            'id' => $task->id,
            'title' => $task->title,
            'description' => $task->description,
            'status' => $task->status,
            'priority' => $task->priority,
            'due_date' => $task->due_date?->toDateString(),
            'assignee' => $task->assignee ? [
                'id' => $task->assignee->id,
                'name' => $task->assignee->name,
            ] : null,
            'creator' => $task->creator ? [
                'id' => $task->creator->id,
                'name' => $task->creator->name,
            ] : null,
            'taskable' => $task->taskable ? [
                'id' => $task->taskable->id,
                'name' => $task->taskable->name,
                'type' => class_basename($task->taskable_type),
            ] : null,
            'created_by' => $task->created_by,
            'assigned_to' => $task->assigned_to,
            'created_at' => $task->created_at->toDateString(),
            'updated_at' => $task->updated_at->toDateString(),
        ]);

        return [
            'data' => $transformedTasks->items(),
            'meta' => [
                'current_page' => $tasks->currentPage(),
                'last_page' => $tasks->lastPage(),
                'per_page' => $tasks->perPage(),
                'total' => $tasks->total(),
                'from' => $tasks->firstItem(),
                'to' => $tasks->lastItem(),
                'prev_page_url' => $tasks->previousPageUrl(),
                'next_page_url' => $tasks->nextPageUrl(),
            ],
            'links' => $tasks->linkCollection()->toArray(),
        ];
    }

    public function getTaskWithRelations(Task $task): array
    {
        $task->load([
            'assignee',
            'creator',
            'taskable',
            'notes' => function ($query) {
                $query->with('user')->latest()->limit(10);
            },
            'activities' => function ($query) {
                $query->with('causer')->latest()->limit(10);
            },
        ]);

        return [
            'task' => [
                'id' => $task->id,
                'title' => $task->title,
                'description' => $task->description,
                'status' => $task->status,
                'priority' => $task->priority,
                'due_date' => $task->due_date?->toISOString(),
                'taskable_type' => $task->taskable_type,
                'taskable_id' => $task->taskable_id,
                'assigned_to' => $task->assigned_to,
                'created_by' => $task->created_by,
                'created_at' => $task->created_at->toISOString(),
                'updated_at' => $task->updated_at->toISOString(),
                'assignee' => $task->assignee ? [
                    'id' => $task->assignee->id,
                    'name' => $task->assignee->name,
                    'email' => $task->assignee->email,
                ] : null,
                'creator' => $task->creator ? [
                    'id' => $task->creator->id,
                    'name' => $task->creator->name,
                ] : null,
                'taskable' => $task->taskable ? [
                    'id' => $task->taskable->id,
                    'name' => $task->taskable->name ?? $task->taskable->title,
                    'type' => class_basename($task->taskable_type),
                ] : null,
            ],
            'notes' => $task->notes->map(fn ($note) => [
                'id' => $note->id,
                'content' => $note->content,
                'user' => [
                    'id' => $note->user->id,
                    'name' => $note->user->name,
                ],
                'created_at' => $note->created_at->toISOString(),
            ]),
            'activities' => $task->activities->map(fn ($activity) => [
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
}
