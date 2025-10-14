<?php

namespace App\Http\Controllers;

use App\Actions\Document\CreateDocumentAction;
use App\Actions\Document\DeleteDocumentAction;
use App\Actions\Document\UpdateDocumentAction;
use App\Concerns\HasMorphTypes;
use App\Http\Requests\Document\CreateDocumentRequest;
use App\Http\Requests\Document\UpdateDocumentRequest;
use App\Models\Document;
use App\Services\Document\DocumentQueryService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class DocumentController extends Controller
{
    use AuthorizesRequests, HasMorphTypes;

    public function __construct(
        private readonly DocumentQueryService $documentQueryService,
        private readonly CreateDocumentAction $createDocumentAction,
        private readonly UpdateDocumentAction $updateDocumentAction,
        private readonly DeleteDocumentAction $deleteDocumentAction,
    ) {}

    /**
     * Display a listing of the documents with filters and pagination
     */
    public function index(): Response
    {
        $this->authorize('viewAny', Document::class);

        $filters = request()->only(['search', 'type', 'uploaded_by', 'documentable_type']);
        $documents = $this->documentQueryService->getFilteredDocuments($filters);
        $transformedDocuments = $this->documentQueryService->transformDocumentsForResponse($documents);

        return Inertia::render('documents/Index', [
            'documents' => $transformedDocuments,
            'filters' => $filters,
            'users' => $this->documentQueryService->getAvailableUsers(),
            'types' => $this->documentQueryService->getDocumentTypes(),
        ]);
    }

    /**
     * Show the form for creating a new document
     */
    public function create(): Response
    {
        $this->authorize('create', Document::class);

        $options = $this->documentQueryService->getRelatedOptions();
        $options['types'] = $this->documentQueryService->getDocumentTypes();

        return Inertia::render('documents/Create', $options);
    }

    /**
     * Store a newly created document in storage
     */
    public function store(CreateDocumentRequest $request): RedirectResponse
    {
        try {
            $this->createDocumentAction->execute($request->validated(), $request->file('file'), $request->user());

            return redirect()->route('documents.index')
                ->with('success', 'Document uploaded successfully.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to upload document: '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified document with all related data
     */
    public function show(Document $document): Response
    {
        $this->authorize('view', $document);

        $documentData = $this->documentQueryService->getDocumentWithRelations($document);

        return Inertia::render('documents/Show', $documentData);
    }

    /**
     * Show the form for editing the specified document
     */
    public function edit(Document $document): Response
    {
        $this->authorize('update', $document);

        $options = $this->documentQueryService->getRelatedOptions();
        $options['types'] = $this->documentQueryService->getDocumentTypes();
        $options['document'] = [
            'id' => $document->id,
            'title' => $document->title,
            'type' => $document->type,
            'file_url' => Storage::url($document->file_path),
            'documentable_type' => $this->getShortMorphType($document->documentable_type),
            'documentable_id' => $document->documentable_id,
        ];

        return Inertia::render('documents/Edit', $options);
    }

    /**
     * Update the specified document in storage
     */
    public function update(UpdateDocumentRequest $request, Document $document): RedirectResponse
    {
        try {
            $this->updateDocumentAction->execute(
                $document, 
                $request->validated(), 
                $request->file('file')
            );

            return redirect()->route('documents.index')
                ->with('success', 'Document updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to update document: '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified document from storage
     */
    public function destroy(Document $document): RedirectResponse
    {
        $this->authorize('delete', $document);

        try {
            $this->deleteDocumentAction->execute($document);

            return redirect()->route('documents.index')
                ->with('success', 'Document deleted successfully.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to delete document: '.$e->getMessage());
        }
    }

    /**
     * Download the document file.
     */
    public function download(Document $document): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $this->authorize('view', $document);

        if (!Storage::disk('public')->exists($document->file_path)) {
            abort(404, 'File not found');
        }

        return Storage::disk('public')->download(
            $document->file_path,
            $document->title . '.' . pathinfo($document->file_path, PATHINFO_EXTENSION)
        );
    }
}