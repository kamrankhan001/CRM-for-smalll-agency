<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Lead;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ProjectController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $this->authorize('viewAny', Project::class);

        $user = Auth::user();

        $projects = Project::query()
            ->with(['client', 'lead', 'creator', 'members'])
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', $request->status);
            })
            ->when($request->filled('client_id'), function ($query) use ($request) {
                $query->where('client_id', $request->client_id);
            })
            ->when($request->filled('lead_id'), function ($query) use ($request) {
                $query->where('lead_id', $request->lead_id);
            })
            ->when($request->filled('created_by'), function ($query) use ($request) {
                $query->where('created_by', $request->created_by);
            })
            ->when($request->date_from, function ($query, $dateFrom) {
                $query->whereDate('start_date', '>=', $dateFrom);
            })
            ->when($request->date_to, function ($query, $dateTo) {
                $query->whereDate('end_date', '<=', $dateTo);
            })
            ->when($user->role === 'member', function ($query) use ($user) {
                $query->where(function ($q) use ($user) {
                    $q->where('created_by', $user->id)
                        ->orWhereHas('members', function ($memberQuery) use ($user) {
                            $memberQuery->where('user_id', $user->id);
                        });
                });
            })
            ->latest()
            ->paginate(10)
            ->through(fn ($project) => [
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

        $clients = Client::select('id', 'name')->get();
        $leads = Lead::select('id', 'name')->get();
        $users = User::select('id', 'name')->get();

        return Inertia::render('projects/Index', [
            'projects' => [
                'data' => $projects->items(),
                'meta' => [
                    'current_page' => $projects->currentPage(),
                    'last_page' => $projects->lastPage(),
                    'per_page' => $projects->perPage(),
                    'total' => $projects->total(),
                    'from' => $projects->firstItem(),
                    'to' => $projects->lastItem(),
                ],
                'links' => $projects->linkCollection()->toArray(),
            ],
            'filters' => $request->only(['search', 'status', 'client_id', 'lead_id', 'created_by', 'date_from', 'date_to']),
            'clients' => $clients,
            'leads' => $leads,
            'users' => $users,
        ]);
    }

    public function create()
    {
        $this->authorize('create', Project::class);

        return Inertia::render('projects/Create', [
            'clients' => Client::select('id', 'name')->get(),
            'leads' => Lead::select('id', 'name')->get(),
            'users' => User::select('id', 'name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Project::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:planning,in_progress,on_hold,completed',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'client_id' => 'nullable|exists:clients,id',
            'lead_id' => 'nullable|exists:leads,id',
            'members' => 'array',
            'members.*' => 'exists:users,id',
        ]);

        $validated['created_by'] = Auth::id();

        $project = Project::create($validated);

        if (!empty($validated['members'])) {
            $project->members()->sync($validated['members']);
        }

        // 🔜 You'll later add a ProjectCreatedNotification here

        return redirect()->route('projects.index')->with('success', 'Project created successfully.');
    }

    public function edit(Project $project)
    {
        $this->authorize('update', $project);

        return Inertia::render('projects/Edit', [
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'description' => $project->description,
                'status' => $project->status,
                'start_date' => $project->start_date?->toDateString(),
                'end_date' => $project->end_date?->toDateString(),
                'client_id' => $project->client_id,
                'lead_id' => $project->lead_id,
                'members' => $project->members->map(fn ($member) => $member->id),
                'creator' => $project->creator ? [
                    'id' => $project->creator->id,
                    'name' => $project->creator->name,
                ] : null,
                'client' => $project->client ? [
                    'id' => $project->client->id,
                    'name' => $project->client->name,
                ] : null,
                'lead' => $project->lead ? [
                    'id' => $project->lead->id,
                    'name' => $project->lead->name,
                ] : null,
                'created_by' => $project->created_by,
                'created_at' => $project->created_at->toDateString(),
                'updated_at' => $project->updated_at->toDateString(),
            ],
            'clients' => Client::select('id', 'name')->get(),
            'leads' => Lead::select('id', 'name')->get(),
            'users' => User::select('id', 'name')->get(),
        ]);
    }

    public function update(Request $request, Project $project)
    {
        $this->authorize('update', $project);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:planning,in_progress,on_hold,completed',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'client_id' => 'nullable|exists:clients,id',
            'lead_id' => 'nullable|exists:leads,id',
            'members' => 'array',
            'members.*' => 'exists:users,id',
        ]);

        $project->update($validated);

        if (isset($validated['members'])) {
            $project->members()->sync($validated['members']);
        }

        return redirect()->route('projects.index')->with('success', 'Project updated successfully.');
    }

    public function destroy(Project $project)
    {
        $this->authorize('delete', $project);

        $project->delete();

        return redirect()->route('projects.index')->with('success', 'Project deleted successfully.');
    }
}