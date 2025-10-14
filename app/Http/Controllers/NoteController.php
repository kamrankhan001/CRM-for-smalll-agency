<?php

namespace App\Http\Controllers;

use App\Actions\Note\CreateNoteAction;
use App\Actions\Note\DeleteNoteAction;
use App\Actions\Note\UpdateNoteAction;
use App\Http\Requests\Note\CreateNoteRequest;
use App\Http\Requests\Note\UpdateNoteRequest;
use App\Models\Note;
use App\Models\User;
use App\Services\Note\NoteQueryService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class NoteController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly NoteQueryService $noteQueryService,
        private readonly CreateNoteAction $createNoteAction,
        private readonly UpdateNoteAction $updateNoteAction,
        private readonly DeleteNoteAction $deleteNoteAction,
    ) {}

    /**
     * Display a listing of the notes with filters and pagination
     */
    public function index(): Response
    {
        $this->authorize('viewAny', Note::class);

        $filters = request()->only(['search', 'noteable_type', 'user_id', 'date_range', 'date_from', 'date_to']);
        $notes = $this->noteQueryService->getFilteredNotes($filters);
        $transformedNotes = $this->noteQueryService->transformNotesForResponse($notes);

        return Inertia::render('notes/Index', [
            'notes' => $transformedNotes,
            'filters' => $filters,
            'users' => $this->noteQueryService->getAvailableUsers(),
        ]);
    }

    /**
     * Show the form for creating a new note
     */
    public function create(): Response
    {
        $this->authorize('create', Note::class);

        $options = $this->noteQueryService->getRelatedOptions();

        return Inertia::render('notes/Create', $options);
    }

    /**
     * Store a newly created note in storage
     */
    public function store(CreateNoteRequest $request): RedirectResponse
    {
        try {
            $this->createNoteAction->execute($request->validated(), $request->user());

            return redirect()->route('notes.index')
                ->with('success', 'Note created successfully.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to create note: '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified note with all related data
     */
    public function show(Note $note): Response
    {
        $this->authorize('view', $note);

        $noteData = $this->noteQueryService->getNoteWithRelations($note);

        return Inertia::render('notes/Show', $noteData);
    }

    /**
     * Show the form for editing the specified note
     */
    public function edit(Note $note): Response
    {
        $this->authorize('update', $note);

        $options = $this->noteQueryService->getRelatedOptions();
        $options['note'] = [
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
        ];

        return Inertia::render('notes/Edit', $options);
    }

    /**
     * Update the specified note in storage
     */
    public function update(UpdateNoteRequest $request, Note $note): RedirectResponse
    {
        try {
            $this->updateNoteAction->execute($note, $request->validated());

            return redirect()->route('notes.index')
                ->with('success', 'Note updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to update note: '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified note from storage
     */
    public function destroy(Note $note): RedirectResponse
    {
        $this->authorize('delete', $note);

        try {
            $this->deleteNoteAction->execute($note);

            return redirect()->route('notes.index')
                ->with('success', 'Note deleted successfully.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to delete note: '.$e->getMessage());
        }
    }
}