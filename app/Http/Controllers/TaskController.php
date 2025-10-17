<?php

namespace App\Http\Controllers;

use App\Actions\Task\CreateTaskAction;
use App\Actions\Task\DeleteTaskAction;
use App\Actions\Task\UpdateTaskAction;
use App\Concerns\HasMorphTypes;
use App\Http\Requests\Task\CreateTaskRequest;
use App\Http\Requests\Task\UpdateTaskRequest;
use App\Models\Client;
use App\Models\Lead;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Services\Task\TaskQueryService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class TaskController extends Controller
{
    use AuthorizesRequests, HasMorphTypes;

    public function __construct(
        private TaskQueryService $taskQueryService,
        private CreateTaskAction $createTaskAction,
        private UpdateTaskAction $updateTaskAction,
        private DeleteTaskAction $deleteTaskAction,
    ) {}

    /**
     * Display a listing of the tasks with filters and pagination
     */
    public function index(): Response
    {
        $this->authorize('viewAny', Task::class);

        $filters = request()->only(['search', 'status', 'assigned_to', 'date_from', 'date_to']);
        $tasks = $this->taskQueryService->getFilteredTasks($filters, auth()->user());
        $transformedTasks = $this->taskQueryService->transformTasksForResponse($tasks);

        $users = User::select('id', 'name')->get();

        return Inertia::render('tasks/Index', [
            'tasks' => $transformedTasks,
            'filters' => $filters,
            'users' => $users,
        ]);
    }

    /**
     * Show the form for creating a new task
     */
    public function create(): Response
    {
        $this->authorize('create', Task::class);

        return Inertia::render('tasks/Create', [
            'users' => User::select('id', 'name')->get(),
            'leads' => Lead::select('id', 'name')->get(),
            'clients' => Client::select('id', 'name')->get(),
            'projects' => Project::select('id', 'name')->get(),
        ]);
    }

    /**
     * Store a newly created task in storage
     */
    public function store(CreateTaskRequest $request): RedirectResponse
    {
        try {
            $this->createTaskAction->execute($request->validated(), $request->user());

            return redirect()->route('tasks.index')
                ->with('success', 'Task created successfully.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to create task: '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified task with all related data
     */
    public function show(Task $task): Response
    {
        $this->authorize('view', $task);

        $taskData = $this->taskQueryService->getTaskWithRelations($task);

        return Inertia::render('tasks/Show', $taskData);
    }

    /**
     * Show the form for editing the specified task
     */
    public function edit(Task $task): Response
    {
        $this->authorize('update', $task);

        return Inertia::render('tasks/Edit', [
            'task' => [
                'id' => $task->id,
                'title' => $task->title,
                'description' => $task->description,
                'status' => $task->status,
                'priority' => $task->priority,
                'due_date' => $task->due_date?->toDateString(),
                'taskable_type' => $this->getShortMorphType($task->taskable_type),
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
            'projects' => Project::select('id', 'name')->get(),
        ]);
    }

    /**
     * Update the specified task in storage
     */
    public function update(UpdateTaskRequest $request, Task $task): RedirectResponse
    {
        try {
            $this->updateTaskAction->execute($task, $request->validated(), $request->user());

            return redirect()->route('tasks.index')
                ->with('success', 'Task updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to update task: '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified task from storage
     */
    public function destroy(Task $task): RedirectResponse
    {
        $this->authorize('delete', $task);

        try {
            $this->deleteTaskAction->execute($task);

            return redirect()->route('tasks.index')
                ->with('success', 'Task deleted successfully.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to delete task: '.$e->getMessage());
        }
    }

    /**
     * Mark task as completed
     */
    public function complete(Task $task): RedirectResponse
    {
        $this->authorize('update', $task);

        try {
            $task->update(['status' => 'completed']);

            return redirect()->route('tasks.show', $task->id)
                ->with('success', 'Task marked as completed.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to complete task: '.$e->getMessage());
        }
    }
}
