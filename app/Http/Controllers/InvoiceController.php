<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Project;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class InvoiceController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $this->authorize('viewAny', Invoice::class);

        $invoices = Invoice::query()
            ->with(['project', 'client', 'creator'])
            ->when($request->search, function ($query, $search) {
                $query->where('title', 'like', "%{$search}%");
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', $request->status);
            })
            ->when($request->date_from, fn ($q, $v) => $q->whereDate('issue_date', '>=', $v))
            ->when($request->date_to, fn ($q, $v) => $q->whereDate('issue_date', '<=', $v))
            ->latest()
            ->paginate(10)
            ->through(fn ($invoice) => [
                'id' => $invoice->id,
                'title' => $invoice->title,
                'amount' => $invoice->amount,
                'amount_paid' => $invoice->amount_paid,
                'status' => $invoice->status,
                'issue_date' => $invoice->issue_date?->toDateString(),
                'due_date' => $invoice->due_date?->toDateString(),
                'paid_at' => $invoice->paid_at?->toDateString(),
                'client' => $invoice->client ? [
                    'id' => $invoice->client->id,
                    'name' => $invoice->client->name,
                ] : null,
                'project' => $invoice->project ? [
                    'id' => $invoice->project->id,
                    'name' => $invoice->project->name,
                ] : null,
                'creator' => $invoice->creator ? [
                    'id' => $invoice->creator->id,
                    'name' => $invoice->creator->name,
                ] : null,
            ]);

        return Inertia::render('invoices/Index', [
            'invoices' => [
                'data' => $invoices->items(),
                'links' => $invoices->linkCollection()->toArray(), // Add this line
                'meta' => [
                    'current_page' => $invoices->currentPage(),
                    'last_page' => $invoices->lastPage(),
                    'per_page' => $invoices->perPage(), // Add this
                    'total' => $invoices->total(),
                    'from' => $invoices->firstItem(), // Add this
                    'to' => $invoices->lastItem(), // Add this
                ],
            ],
            'filters' => $request->only(['search', 'status', 'date_from', 'date_to']),
        ]);
    }

    public function create()
    {
        $this->authorize('create', Invoice::class);

        return Inertia::render('invoices/Create', [
            'clients' => Client::select('id', 'name')->get(),
            'projects' => Project::select('id', 'name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Invoice::class);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'amount_paid' => 'nullable|numeric|min:0|max:'.($request->amount ?? 0),
            'status' => 'required|in:draft,sent,partially_paid,paid,cancelled',
            'issue_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issue_date',
            'notes' => 'nullable|string',
            'client_id' => 'nullable|exists:clients,id',
            'project_id' => 'nullable|exists:projects,id',
        ]);

        $validated['created_by'] = Auth::id();

        Invoice::create($validated);

        return redirect()->route('invoices.index')->with('success', 'Invoice created successfully.');
    }

    public function edit(Invoice $invoice)
    {
        $this->authorize('update', $invoice);

        return Inertia::render('invoices/Edit', [
            'invoice' => [
                'id' => $invoice->id,
                'title' => $invoice->title,
                'amount' => $invoice->amount,
                'amount_paid' => $invoice->amount_paid,
                'status' => $invoice->status,
                'issue_date' => $invoice->issue_date?->toDateString(),
                'due_date' => $invoice->due_date?->toDateString(),
                'notes' => $invoice->notes,
                'client_id' => $invoice->client_id,
                'project_id' => $invoice->project_id,
            ],
            'clients' => Client::select('id', 'name')->get(),
            'projects' => Project::select('id', 'name')->get(),
        ]);
    }

    public function update(Request $request, Invoice $invoice)
    {
        $this->authorize('update', $invoice);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'amount_paid' => 'nullable|numeric|min:0|max:'.($request->amount ?? 0),
            'status' => 'required|in:draft,sent,partially_paid,paid,cancelled',
            'issue_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issue_date',
            'notes' => 'nullable|string',
            'client_id' => 'nullable|exists:clients,id',
            'project_id' => 'nullable|exists:projects,id',
        ]);

        $invoice->update($validated);

        return redirect()->route('invoices.index')->with('success', 'Invoice updated successfully.');
    }

    public function destroy(Invoice $invoice)
    {
        $this->authorize('delete', $invoice);

        $invoice->delete();

        return redirect()->route('invoices.index')->with('success', 'Invoice deleted successfully.');
    }
}
