<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Document;
use App\Models\Lead;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class DocumentController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $this->authorize('viewAny', Document::class);

        $documents = Document::query()
            ->with(['documentable', 'uploader'])
            ->when($request->search, fn ($q, $search) => $q->where('title', 'like', "%{$search}%"))
            ->when($request->type, fn ($q, $type) => $q->where('type', $type))
            ->when($request->uploaded_by, fn ($q, $uploadedBy) => $q->where('uploaded_by', $uploadedBy))
            ->when($request->documentable_type, function ($q, $documentableType) {
                $map = [
                    'lead' => Lead::class,
                    'client' => Client::class,
                    'project' => Project::class,
                ];
                $q->where('documentable_type', $map[$documentableType]);
            })
            ->latest()
            ->paginate(10)
            ->through(fn ($doc) => [
                'id' => $doc->id,
                'title' => $doc->title,
                'type' => $doc->type,
                'file_path' => Storage::url($doc->file_path),
                'uploader' => $doc->uploader?->only(['id', 'name']),
                'documentable' => $doc->documentable ? [
                    'id' => $doc->documentable->id,
                    'name' => $doc->documentable->name,
                    'type' => class_basename($doc->documentable_type),
                ] : null,
                'created_at' => $doc->created_at->toDateString(),
            ]);

        $documentTypes = ['proposal', 'contract', 'invoice', 'report', 'brief', 'misc'];

        return Inertia::render('documents/Index', [
            'documents' => [
                'data' => $documents->items(),
                'meta' => [
                    'current_page' => $documents->currentPage(),
                    'last_page' => $documents->lastPage(),
                    'per_page' => $documents->perPage(),
                    'total' => $documents->total(),
                    'from' => $documents->firstItem(),
                    'to' => $documents->lastItem(),
                ],
                'links' => $documents->linkCollection()->toArray(),
            ],
            'filters' => $request->only(['search', 'type', 'uploaded_by', 'documentable_type']), // Add documentable_type
            'users' => User::select('id', 'name')->get(),
            'types' => $documentTypes,
        ]);
    }

    public function create()
    {
        $this->authorize('create', Document::class);

        return Inertia::render('documents/Create', [
            'leads' => Lead::select('id', 'name')->get(),
            'clients' => Client::select('id', 'name')->get(),
            'projects' => Project::select('id', 'name')->get(),
            'types' => ['proposal', 'contract', 'invoice', 'report', 'brief', 'misc'],
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Document::class);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|string|in:proposal,contract,invoice,report,brief,misc',
            'file' => 'required|file|mimes:pdf,doc,docx,png,jpg,jpeg|max:5120',
            'documentable_type' => 'required|string|in:lead,client,project',
            'documentable_id' => 'required|integer',
        ]);

        $map = [
            'lead' => Lead::class,
            'client' => Client::class,
            'project' => Project::class,
        ];

        $validated['documentable_type'] = $map[$validated['documentable_type']];
        $validated['uploaded_by'] = Auth::id();
        $validated['file_path'] = $request->file('file')->store('documents', 'public');

        Document::create($validated);

        return redirect()->route('documents.index')->with('success', 'Document uploaded successfully.');
    }

    public function edit(Document $document)
    {
        $this->authorize('update', $document);

        return Inertia::render('documents/Edit', [
            'document' => [
                'id' => $document->id,
                'title' => $document->title,
                'type' => $document->type,
                'file_url' => Storage::url($document->file_path),
                'documentable_type' => strtolower(class_basename($document->documentable_type)),
                'documentable_id' => $document->documentable_id,
            ],
            'leads' => Lead::select('id', 'name')->get(),
            'clients' => Client::select('id', 'name')->get(),
            'projects' => Project::select('id', 'name')->get(),
            'types' => ['proposal', 'contract', 'invoice', 'report', 'brief', 'misc'],
        ]);
    }

    public function update(Request $request, Document $document)
    {
        $this->authorize('update', $document);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|string|in:proposal,contract,invoice,report,brief,misc',
            'file' => 'nullable|file|mimes:pdf,doc,docx,png,jpg,jpeg|max:5120',
            'documentable_type' => 'required|string|in:lead,client,project',
            'documentable_id' => 'required|integer',
        ]);

        $map = [
            'lead' => Lead::class,
            'client' => Client::class,
            'project' => Project::class,
        ];

        $validated['documentable_type'] = $map[$validated['documentable_type']];

        if ($request->hasFile('file')) {
            Storage::disk('public')->delete($document->file_path);
            $validated['file_path'] = $request->file('file')->store('documents', 'public');
        }

        $document->update($validated);

        return redirect()->route('documents.index')->with('success', 'Document updated successfully.');
    }

    public function destroy(Document $document)
    {
        $this->authorize('delete', $document);

        Storage::disk('public')->delete($document->file_path);
        $document->delete();

        return redirect()->route('documents.index')->with('success', 'Document deleted successfully.');
    }
}
