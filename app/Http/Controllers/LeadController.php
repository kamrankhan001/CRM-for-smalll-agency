<?php

namespace App\Http\Controllers;

use App\Actions\Lead\ConvertLeadToClientAction;
use App\Actions\Lead\CreateLeadAction;
use App\Actions\Lead\UpdateLeadAction;
use App\Http\Requests\Lead\ImportLeadsRequest;
use App\Http\Requests\Lead\StoreLeadRequest;
use App\Http\Requests\Lead\UpdateLeadRequest;
use App\Jobs\ImportLeadsJob;
use App\Models\Lead;
use App\Models\User;
use App\Services\Lead\LeadExportService;
use App\Services\Lead\LeadQueryService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LeadController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private LeadQueryService $leadQueryService,
        private LeadExportService $leadExportService,
        private CreateLeadAction $createLeadAction,
        private UpdateLeadAction $updateLeadAction,
        private ConvertLeadToClientAction $convertLeadToClientAction
    ) {}

    /**
     * Display a listing of the leads with filters and pagination
     */
    public function index(): Response
    {
        $this->authorize('viewAny', Lead::class);

        $filters = request()->only(['search', 'status', 'assigned_to', 'date_from', 'date_to']);
        $leads = $this->leadQueryService->getFilteredLeads($filters, auth()->user());
        $transformedLeads = $this->leadQueryService->transformLeadsForResponse($leads);

        $users = User::select('id', 'name')->get();

        return Inertia::render('leads/Index', [
            'leads' => $transformedLeads,
            'filters' => $filters,
            'users' => $users,
        ]);
    }

    /**
     * Show the form for creating a new lead
     */
    public function create(): Response
    {
        $this->authorize('create', Lead::class);

        $users = User::select('id', 'name')->get();

        return Inertia::render('leads/Create', [
            'users' => $users,
        ]);
    }

    /**
     * Store a newly created lead in storage
     */
    public function store(StoreLeadRequest $request): RedirectResponse
    {
        $this->createLeadAction->execute($request->validated(), $request->user());

        return redirect()->route('leads.index')
            ->with('success', 'Lead created successfully.');
    }

    /**
     * Display the specified lead with all related data
     */
    public function show(Lead $lead): Response
    {
        $this->authorize('view', $lead);

        // Eager load all necessary relationships to avoid N+1 queries
        $lead->load([
            'creator',
            'assignee',
            'notes.user',
            'activities.causer',
            'tasks',
            'documents',
            'client',
        ]);

        return Inertia::render('leads/Show', [
            'lead' => $lead,
            'notes' => $lead->notes,
            'activities' => $lead->activities,
            'tasks' => $lead->tasks,
            'documents' => $lead->documents,
        ]);
    }

    /**
     * Show the form for editing the specified lead
     */
    public function edit(Lead $lead): Response
    {
        $this->authorize('update', $lead);

        $users = User::select('id', 'name')->get();

        return Inertia::render('leads/Edit', [
            'lead' => [
                'id' => $lead->id,
                'name' => $lead->name,
                'email' => $lead->email,
                'phone' => $lead->phone,
                'company' => $lead->company,
                'source' => $lead->source,
                'status' => $lead->status,
                'assignee' => $lead->assignee ? [
                    'id' => $lead->assignee->id,
                    'name' => $lead->assignee->name,
                ] : null,
                'creator' => $lead->creator ? [
                    'id' => $lead->creator->id,
                    'name' => $lead->creator->name,
                ] : null,
                'created_by' => $lead->created_by,
                'assigned_to' => $lead->assigned_to,
                'created_at' => $lead->created_at->toDateString(),
                'updated_at' => $lead->updated_at->toDateString(),
            ],
            'users' => $users,
        ]);
    }

    /**
     * Update the specified lead in storage
     */
    public function update(UpdateLeadRequest $request, Lead $lead): RedirectResponse
    {
        try {
            $this->updateLeadAction->execute($lead, $request->validated(), $request->user());

            return redirect()->route('leads.index')
                ->with('success', 'Lead updated successfully.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to update lead: '.$e->getMessage());
        }
    }

    /**
     * Remove the specified lead from storage
     */
    public function destroy(Lead $lead): RedirectResponse
    {
        $this->authorize('delete', $lead);

        $lead->delete();

        return redirect()->route('leads.index')
            ->with('success', 'Lead deleted successfully.');
    }

    /**
     * Export leads to Excel with filters
     */
    public function export(): RedirectResponse
    {
        $this->authorize('viewAny', Lead::class);

        $filters = request()->only(['search', 'status', 'assigned_to', 'date_from', 'date_to']);
        $this->leadExportService->startExport($filters, auth()->id());

        return back()->with('success', 'Your export has started and will be ready for download in a few seconds.');
    }

    /**
     * Download the generated export file
     */
    public function downloadExport(): BinaryFileResponse
    {
        $userId = auth()->id();
        $path = $this->leadExportService->getExportFilePath($userId);

        if (! $path) {
            abort(404, 'Export file link has expired or not found.');
        }

        if (! Storage::disk('local')->exists($path)) {
            abort(404, 'Export file not found on server. Please try again.');
        }

        $fullPath = Storage::disk('local')->path($path);
        $filename = 'leads-export-'.now()->format('Y-m-d').'.xlsx';

        // Clear cache
        $this->leadExportService->clearExportCache($userId);

        return response()->download($fullPath, $filename)->deleteFileAfterSend(true);
    }

    /**
     * Import leads from Excel file
     */
    public function import(ImportLeadsRequest $request): RedirectResponse
    {
        try {
            $path = $request->file('file')->store('temp_imports', 'local');

            // Dispatch background import job
            ImportLeadsJob::dispatch($path, auth()->id());

            return back()->with('success', 'Leads import is being processed in the background.');

        } catch (\Exception $e) {
            return back()->with('error', 'Error uploading file: '.$e->getMessage());
        }
    }

    /**
     * Download sample import template
     */
    public function downloadSample(): BinaryFileResponse
    {
        $this->authorize('create', Lead::class);

        $sampleData = [
            [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'phone' => '+1234567890',
                'company' => 'ABC Company',
                'source' => 'website',
                'status' => 'new',
                'assigned_to' => 'User Name',
            ],
            [
                'name' => 'Jane Smith',
                'email' => 'jane@example.com',
                'phone' => '+0987654321',
                'company' => 'XYZ Corp',
                'source' => 'referral',
                'status' => 'contacted',
                'assigned_to' => '',
            ],
        ];

        return Excel::download(new class($sampleData) implements \Maatwebsite\Excel\Concerns\FromArray, \Maatwebsite\Excel\Concerns\WithHeadings
        {
            protected $data;

            public function __construct($data)
            {
                $this->data = $data;
            }

            public function array(): array
            {
                return $this->data;
            }

            public function headings(): array
            {
                return [
                    'name',
                    'email',
                    'phone',
                    'company',
                    'source',
                    'status',
                    'assigned_to',
                ];
            }
        }, 'leads-import-template.xlsx');
    }

    /**
     * Convert lead to client
     */
    public function convert(Lead $lead): RedirectResponse
    {
        $this->authorize('update', $lead);

        try {
            $this->convertLeadToClientAction->execute($lead, auth()->user());

            return redirect()->route('leads.show', $lead)
                ->with('success', 'Lead successfully converted to client.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to convert lead to client: '.$e->getMessage());
        }
    }
}
