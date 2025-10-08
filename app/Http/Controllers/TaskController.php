<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Lead;
use App\Models\Task;
use App\Models\User;
use App\Models\Project;
use App\Notifications\TaskAssignedNotification;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class TaskController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $this->authorize('viewAny', Task::class);

        $user = Auth::user();

        $tasks = Task::query()
            ->with(['assignee', 'creator', 'taskable'])
            ->when($request->search, function ($query, $search) {
                $query->where('title', 'like', "%{$search}%");
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', $request->status);
            })
            ->when($request->filled('assigned_to'), function ($query) use ($request) {
                $query->where('assigned_to', $request->assigned_to);
            })
            ->when($request->date_from, function ($query, $dateFrom) {
                $query->whereDate('due_date', '>=', $dateFrom);
            })
            ->when($request->date_to, function ($query, $dateTo) {
                $query->whereDate('due_date', '<=', $dateTo);
            })
            ->when($user->role === 'member', function ($query) use ($user) {
                $query->where(function ($q) use ($user) {
                    $q->where('assigned_to', $user->id)
                        ->orWhere('created_by', $user->id);
                });
            })
            ->latest()
            ->paginate(10)
            ->through(fn ($task) => [
                'id' => $task->id,
                'title' => $task->title,
                'description' => $task->description,
                'status' => $task->status,
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

        $users = User::select('id', 'name')->get();

        return Inertia::render('tasks/Index', [
            'tasks' => [
                'data' => $tasks->items(),
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
            ],
            'filters' => $request->only(['search', 'status', 'assigned_to', 'date_from', 'date_to']),
            'users' => $users,
        ]);
    }

    public function create()
    {
        $this->authorize('create', Task::class);

        return Inertia::render('tasks/Create', [
            'users' => User::select('id', 'name')->get(),
            'leads' => Lead::select('id', 'name')->get(),
            'clients' => Client::select('id', 'name')->get(),
            'projects' => Project::select('id', 'name')->get(), // Add this line
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Task::class);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:pending,in_progress,completed',
            'due_date' => 'nullable|date',
            'taskable_type' => 'required|string|in:lead,client,project',
            'taskable_id' => 'required|integer',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $validated['taskable_type'] = match($validated['taskable_type']) {
            'lead' => Lead::class,
            'client' => Client::class,
            'project' => Project::class,
        };

        $validated['created_by'] = Auth::id();

        $task = Task::create($validated);

        // Send notification to assigned user
        if ($task->assigned_to && $task->assigned_to != Auth::id()) {
            $assignedUser = User::find($task->assigned_to);
            $assignedUser->notify(new TaskAssignedNotification($task));
        }

        return redirect()->route('tasks.index')->with('success', 'Task created successfully.');
    }

    public function edit(Task $task)
    {
        $this->authorize('update', $task);

        return Inertia::render('tasks/Edit', [
            'task' => [
                'id' => $task->id,
                'title' => $task->title,
                'description' => $task->description,
                'status' => $task->status,
                'due_date' => $task->due_date?->toDateString(),
                'taskable_type' => $task->taskable_type === 'App\\Models\\Lead' ? 'lead' : 'client',
                'taskable_id' => $task->taskable_id,
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
            ],
            'users' => User::select('id', 'name')->get(),
            'leads' => Lead::select('id', 'name')->get(),
            'clients' => Client::select('id', 'name')->get(),
            'projects' => Project::select('id', 'name')->get(), // Add this line
        ]);
    }

    public function update(Request $request, Task $task)
    {
        $this->authorize('update', $task);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:pending,in_progress,completed',
            'due_date' => 'nullable|date',
            'taskable_type' => 'required|string|in:lead,client,project',
            'taskable_id' => 'required|integer',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        // Check if assignment is changing
        $oldAssignedTo = $task->assigned_to;
        $newAssignedTo = $validated['assigned_to'] ?? null;

        $validated['taskable_type'] = match($validated['taskable_type']) {
            'lead' => Lead::class,
            'client' => Client::class,
            'project' => Project::class,
        };


        $task->update($validated);

        // Send notification if assigned to a different user (and not the current user)
        if ($newAssignedTo && $newAssignedTo != $oldAssignedTo && $newAssignedTo != Auth::id()) {
            $assignedUser = User::find($newAssignedTo);
            $assignedUser->notify(new TaskAssignedNotification($task));
        }

        return redirect()->route('tasks.index')->with('success', 'Task updated successfully.');
    }

    public function destroy(Task $task)
    {
        $this->authorize('delete', $task);

        $task->delete();

        return redirect()->route('tasks.index')->with('success', 'Task deleted successfully.');
    }
}
