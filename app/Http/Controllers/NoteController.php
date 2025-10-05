<?php

namespace App\Http\Controllers;

use App\Models\Note;
use App\Models\Lead;
use App\Models\Client;
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
            ->latest()
            ->paginate(10)
            ->through(fn ($note) => [
                'id' => $note->id,
                'content' => $note->content,
                'user' => [
                    'id' => $note->user->id,
                    'name' => $note->user->name,
                ],
                'noteable' => $note->noteable ? [
                    'id' => $note->noteable->id,
                    'name' => $note->noteable->name,
                    'type' => class_basename($note->noteable_type),
                ] : null,
                'created_at' => $note->created_at->toISOString(),
                'updated_at' => $note->updated_at->toISOString(),
            ]);

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
        ]);
    }

    public function create()
    {
        $this->authorize('create', Note::class);

        return Inertia::render('notes/Create', [
            'leads' => Lead::select('id', 'name')->get(),
            'clients' => Client::select('id', 'name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Note::class);

        $validated = $request->validate([
            'content' => 'required|string|max:1000',
            'noteable_id' => 'required|integer',
            'noteable_type' => 'required|string|in:lead,client',
        ]);

        // Convert to full model class names
        $validated['noteable_type'] = $validated['noteable_type'] === 'lead'
            ? 'App\\Models\\Lead'
            : 'App\\Models\\Client';
        $validated['user_id'] = auth()->id();

        Note::create($validated);

        return redirect()->route('notes.index')->with('success', 'Note created successfully.');
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
                'noteable' => $note->noteable ? [
                    'id' => $note->noteable->id,
                    'name' => $note->noteable->name,
                    'type' => class_basename($note->noteable_type),
                ] : null,
                'created_at' => $note->created_at->toISOString(),
                'updated_at' => $note->updated_at->toISOString(),
            ],
            'leads' => Lead::select('id', 'name')->get(),
            'clients' => Client::select('id', 'name')->get(),
        ]);
    }

    public function update(Request $request, Note $note)
    {
        $this->authorize('update', $note);

        $validated = $request->validate([
            'content' => 'required|string|max:1000',
            'noteable_id' => 'required|integer',
            'noteable_type' => 'required|string|in:lead,client',
        ]);

        // Convert to full model class names
        $validated['noteable_type'] = $validated['noteable_type'] === 'lead'
            ? 'App\\Models\\Lead'
            : 'App\\Models\\Client';

        $note->update($validated);

        return redirect()->route('notes.index')->with('success', 'Note updated successfully.');
    }

    public function destroy(Note $note)
    {
        $this->authorize('delete', $note);

        $note->delete();

        return back()->with('success', 'Note deleted successfully.');
    }
}