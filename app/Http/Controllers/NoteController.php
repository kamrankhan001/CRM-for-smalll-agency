<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Lead;
use App\Models\Note;
use App\Models\Project;
use App\Models\User;
use App\Notifications\NoteAddedNotification;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Inertia\Inertia;

class NoteController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $this->authorize('viewAny', Note::class);

        $notes = Note::with(['user', 'noteable'])
            ->when($request->search, function ($query, $search) {
                $query->where('content', 'like', "%{$search}%");
            })
            ->when($request->filled('noteable_type'), function ($query) use ($request) {
                $query->where('noteable_type', match ($request->noteable_type) {
                    'lead' => 'App\\Models\\Lead',
                    'client' => 'App\\Models\\Client',
                    'project' => 'App\\Models\\Project',
                    default => 'App\\Models\\Lead'
                });
            })
            ->when($request->filled('user_id'), function ($query) use ($request) {
                $query->where('user_id', $request->user_id);
            })
            ->when($request->date_range, function ($query) use ($request) {
                $dateRanges = [
                    '7_days' => now()->subDays(7),
                    '15_days' => now()->subDays(15),
                    '30_days' => now()->subDays(30),
                    'custom' => $request->date_from ?: now()->subDays(30),
                ];

                if ($request->date_range === 'custom' && $request->date_from && $request->date_to) {
                    $query->whereBetween('created_at', [
                        $request->date_from,
                        $request->date_to,
                    ]);
                } else {
                    $query->where('created_at', '>=', $dateRanges[$request->date_range]);
                }
            })
            ->latest()
            ->paginate(10)
            ->through(fn ($note) => [
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

        $users = User::select('id', 'name')->get();

        return Inertia::render('notes/Index', [
            'notes' => [
                'data' => $notes->items(),
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
            ],
            'filters' => $request->only(['search', 'noteable_type', 'user_id', 'date_range', 'date_from', 'date_to']),
            'users' => $users,
        ]);
    }

    public function create()
    {
        $this->authorize('create', Note::class);

        return Inertia::render('notes/Create', [
            'leads' => Lead::select('id', 'name')->get(),
            'clients' => Client::select('id', 'name')->get(),
            'projects' => Project::select('id', 'name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Note::class);

        $validated = $request->validate([
            'content' => 'required|string|max:1000',
            'noteable_id' => 'required|integer',
            'noteable_type' => 'required|string|in:lead,client,project',
        ]);

        // Convert to full model class names
        $validated['noteable_type'] = match ($validated['noteable_type']) {
            'lead' => 'App\\Models\\Lead',
            'client' => 'App\\Models\\Client',
            'project' => 'App\\Models\\Project',
            default => 'App\\Models\\Lead'
        };

        $validated['user_id'] = auth()->id();

        $note = Note::create($validated);

        // Send notification to ALL users in the system
        $allUsers = User::all();
        foreach ($allUsers as $user) {
            $user->notify(new NoteAddedNotification($note));
        }

        return redirect()->route('notes.index')->with('success', 'Note created successfully.');
    }

    /**
     * Display the specified note.
     */
    public function show(Note $note)
    {
        $this->authorize('view', $note);

        $note->load([
            'user',
            'noteable',
            'activities' => function ($query) {
                $query->with('causer')->latest()->limit(10);
            },
        ]);

        return Inertia::render('notes/Show', [
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
        ]);
    }

    public function edit(Note $note)
    {
        $this->authorize('update', $note);

        return Inertia::render('notes/Edit', [
            'note' => [
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
            ],
            'leads' => Lead::select('id', 'name')->get(),
            'projects' => Project::select('id', 'name')->get(),
            'clients' => Client::select('id', 'name')->get(),
        ]);
    }

    public function update(Request $request, Note $note)
    {
        $this->authorize('update', $note);

        $validated = $request->validate([
            'content' => 'required|string|max:1000',
            'noteable_id' => 'required|integer',
            'noteable_type' => 'required|string|in:lead,client,project',
        ]);

        // Convert to full model class names
        $validated['noteable_type'] = match ($validated['noteable_type']) {
            'lead' => 'App\\Models\\Lead',
            'client' => 'App\\Models\\Client',
            'project' => 'App\\Models\\Project',
            default => 'App\\Models\\Lead'
        };

        $note->update($validated);

        return redirect()->route('notes.index')->with('success', 'Note updated successfully.');
    }

    public function destroy(Note $note)
    {
        $this->authorize('delete', $note);

        $note->delete();

        return redirect()->route('notes.index')->with('success', 'Note deleted successfully.');
    }
}
