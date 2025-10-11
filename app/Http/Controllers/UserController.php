<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class UserController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $this->authorize('viewAny', User::class);

        $users = User::query()
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('role'), function ($query) use ($request) {
                $query->where('role', $request->role);
            })
            ->when($request->date_from, function ($query, $dateFrom) {
                $query->whereDate('created_at', '>=', $dateFrom);
            })
            ->when($request->date_to, function ($query, $dateTo) {
                $query->whereDate('created_at', '<=', $dateTo);
            })
            ->latest()
            ->paginate(10)
            ->through(fn ($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'created_at' => $user->created_at->toDateString(),
            ]);

        return Inertia::render('users/Index', [
            'users' => [
                'data' => $users->items(),
                'meta' => [
                    'current_page' => $users->currentPage(),
                    'last_page' => $users->lastPage(),
                    'per_page' => $users->perPage(),
                    'total' => $users->total(),
                    'prev_page_url' => $users->previousPageUrl(),
                    'next_page_url' => $users->nextPageUrl(),
                ],
                'links' => $users->linkCollection()->toArray(),
            ],
            'filters' => $request->only(['search', 'role', 'date_from', 'date_to']),
        ]);
    }

    public function create()
    {
        $this->authorize('create', User::class);

        return Inertia::render('users/Create');
    }

    public function store(Request $request)
    {
        $this->authorize('create', User::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'role' => ['required', Rule::in(['admin', 'manager', 'member'])],
        ]);

        $validated['password'] = bcrypt($validated['password']);

        User::create($validated);

        return redirect()->route('users.index')
            ->with('success', 'User created.');
    }

    /**
     * Display the specified user.
     */
    public function show(User $user)
    {
        $this->authorize('view', $user);

        $user->load([
            'assignedClients',
            'assignedLeads',
            'tasks' => function ($query) {
                $query->latest()->limit(5);
            },
            'projects' => function ($query) {
                $query->latest()->limit(5);
            },
            'ownedProjects' => function ($query) {
                $query->latest()->limit(5);
            },
            'uploadedDocuments' => function ($query) {
                $query->latest()->limit(5);
            },
        ]);

        return Inertia::render('users/Show', [
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
        ]);
    }

    public function edit(User $user)
    {
        $this->authorize('update', $user);

        return Inertia::render('users/Edit', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
        ]);
    }

    public function update(Request $request, User $user)
    {
        $this->authorize('update', $user);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                Rule::unique('users')->ignore($user->id),
            ],
            'password' => 'nullable|string|min:6|confirmed',
            'role' => ['required', Rule::in(['admin', 'manager', 'member'])],
        ]);

        if (isset($validated['password']) && $validated['password']) {
            $validated['password'] = bcrypt($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return redirect()->route('users.index')
            ->with('success', 'User updated.');
    }

    public function destroy(User $user)
    {
        $this->authorize('delete', $user);

        $user->delete();

        return redirect()->route('users.index')
            ->with('success', 'User deleted.');
    }
}
