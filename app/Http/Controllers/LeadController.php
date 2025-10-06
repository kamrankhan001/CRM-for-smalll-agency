<?php

namespace App\Http\Controllers;

use App\Exports\LeadsExport;
use App\Imports\LeadsImport;
use App\Models\Lead;
use App\Models\User;
use App\Notifications\LeadAssignedNotification;
use App\Notifications\LeadConvertedNotification;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;
use App\Models\Client;

class LeadController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of the leads.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Lead::class);

        $leads = Lead::with(['creator', 'assignee'])
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('company', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', $request->status);
            })
            ->when($request->filled('assigned_to'), function ($query) use ($request) {
                $query->where('assigned_to', $request->assigned_to);
            })
            ->when($request->date_from, function ($query, $dateFrom) {
                $query->whereDate('created_at', '>=', $dateFrom);
            })
            ->when($request->date_to, function ($query, $dateTo) {
                $query->whereDate('created_at', '<=', $dateTo);
            })
            ->when($request->user()->role === 'member', function ($query) use ($request) {
                // Members only see their own or assigned leads
                $query->where('created_by', $request->user()->id)
                    ->orWhere('assigned_to', $request->user()->id);
            })
            ->latest()
            ->paginate(10)
            ->through(fn ($lead) => [
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
            ]);

        $users = User::select('id', 'name')->get();

        return Inertia::render('leads/Index', [
            'leads' => [
                'data' => $leads->items(),
                'meta' => [
                    'current_page' => $leads->currentPage(),
                    'last_page' => $leads->lastPage(),
                    'per_page' => $leads->perPage(),
                    'total' => $leads->total(),
                    'from' => $leads->firstItem(),
                    'to' => $leads->lastItem(),
                    'prev_page_url' => $leads->previousPageUrl(),
                    'next_page_url' => $leads->nextPageUrl(),
                ],
                'links' => $leads->linkCollection()->toArray(),
            ],
            'filters' => $request->only(['search', 'status', 'assigned_to', 'date_from', 'date_to']),
            'users' => $users,
        ]);
    }

    /**
     * Show the form for creating a new lead.
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
     * Store a newly created lead in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create', Lead::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:20',
            'company' => 'nullable|string|max:255',
            'source' => 'nullable|string|max:255',
            'status' => 'required|in:new,contacted,qualified,lost',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $validated['created_by'] = $request->user()->id;

        $lead = Lead::create($validated);

        // Send notification if assigned to someone else
        if ($lead->assigned_to && $lead->assigned_to != $request->user()->id) {
            $assignedUser = User::find($lead->assigned_to);
            $assignedUser->notify(new LeadAssignedNotification($lead));
        }

        return redirect()->route('leads.index')
            ->with('success', 'Lead created successfully.');
    }

    /**
     * Show the form for editing the specified lead.
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
     * Update the specified lead in storage.
     */
    public function update(Request $request, Lead $lead)
    {
        $this->authorize('update', $lead);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:20',
            'company' => 'nullable|string|max:255',
            'source' => 'nullable|string|max:255',
            'status' => 'required|in:new,contacted,qualified,lost',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        // Check if status is changing to qualified
        $isConvertingToClient = $validated['status'] === 'qualified' && $lead->status !== 'qualified';

        // Check if assignment is changing to a different user
        $oldAssignedTo = $lead->assigned_to;
        $newAssignedTo = $validated['assigned_to'] ?? null;

        try {
            DB::transaction(function () use ($lead, $validated, $isConvertingToClient, $request, $newAssignedTo, $oldAssignedTo) {
                $lead->update($validated);

                // Convert lead to client if status changed to qualified
                if ($isConvertingToClient) {
                    $client = Client::create([
                        'name' => $lead->name,
                        'email' => $lead->email,
                        'phone' => $lead->phone,
                        'company' => $lead->company,
                        'lead_id' => $lead->id,
                        'assigned_to' => null, // Default unassigned
                        'created_by' => $request->user()->id,
                    ]);

                    // Send notification to admin users
                    $adminUsers = User::where('role', 'admin')->get();
                    foreach ($adminUsers as $admin) {
                        $admin->notify(new LeadConvertedNotification($lead, $client));
                    }
                }

                // Send assignment notification
                if ($newAssignedTo && $newAssignedTo != $oldAssignedTo && $newAssignedTo != $request->user()->id) {
                    $assignedUser = User::find($newAssignedTo);
                    $assignedUser->notify(new LeadAssignedNotification($lead));
                }
            });

            return redirect()->route('leads.index')
                ->with('success', 'Lead updated successfully.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to update lead: '.$e->getMessage());
        }
    }

    /**
     * Remove the specified lead from storage.
     */
    public function destroy(Lead $lead)
    {
        $this->authorize('delete', $lead);

        $lead->delete();

        return redirect()->route('leads.index')
            ->with('success', 'Lead deleted successfully.');
    }

    /**
     * Export leads to Excel
     */
    public function export(Request $request)
    {
        $this->authorize('viewAny', Lead::class);

        $filters = $request->only(['search', 'status', 'assigned_to', 'date_from', 'date_to']);

        // Clean up empty filters
        $filters = array_filter($filters, function ($value) {
            return $value !== null && $value !== '';
        });

        return Excel::download(new LeadsExport($filters), 'leads-'.date('Y-m-d').'.xlsx');
    }

    /**
     * Import leads from Excel
     */
    public function import(Request $request)
    {
        $this->authorize('create', Lead::class);

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240', // 10MB max
        ]);

        try {
            $import = new LeadsImport;
            Excel::import($import, $request->file('file'));

            $importedCount = $import->getImportedCount();
            $errors = $import->getErrors();

            if (! empty($errors)) {
                return redirect()->back()->with([
                    'warning' => "Imported {$importedCount} leads, but encountered ".count($errors).' errors.',
                    'import_errors' => $errors,
                ]);
            }

            return redirect()->back()->with('success', "Successfully imported {$importedCount} leads.");

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error importing file: '.$e->getMessage());
        }
    }

    /**
     * Download sample import template
     */
    /**
     * Download sample import template
     */
    public function downloadSample()
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
                'assigned_to' => 'User Name', // Optional: assignee's name
            ],
            [
                'name' => 'Jane Smith',
                'email' => 'jane@example.com',
                'phone' => '+0987654321',
                'company' => 'XYZ Corp',
                'source' => 'referral',
                'status' => 'contacted',
                'assigned_to' => '', // Leave empty for no assignment
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
}
