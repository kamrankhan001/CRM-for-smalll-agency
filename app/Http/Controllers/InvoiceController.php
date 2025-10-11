<?php

namespace App\Http\Controllers;

use App\Mail\InvoiceMail;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\Document;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class InvoiceController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $this->authorize('viewAny', Invoice::class);

        $invoices = Invoice::query()
            ->with(['project', 'client', 'creator'])
            ->when($request->search, function ($query, $search) {
                $query->where('title', 'like', "%{$search}%")
                    ->orWhere('invoice_number', 'like', "%{$search}%");
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
                'invoice_number' => $invoice->invoice_number,
                'title' => $invoice->title,
                'amount' => $invoice->amount,
                'amount_paid' => $invoice->amount_paid,
                'balance' => $invoice->amount - $invoice->amount_paid,
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
                'links' => $invoices->linkCollection()->toArray(),
                'meta' => [
                    'current_page' => $invoices->currentPage(),
                    'last_page' => $invoices->lastPage(),
                    'per_page' => $invoices->perPage(),
                    'total' => $invoices->total(),
                    'from' => $invoices->firstItem(),
                    'to' => $invoices->lastItem(),
                ],
            ],
            'filters' => $request->only(['search', 'status', 'date_from', 'date_to']),
        ]);
    }

    public function create()
    {
        $this->authorize('create', Invoice::class);

        return Inertia::render('invoices/Create', [
            'clients' => Client::select('id', 'name', 'email')->get(),
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
            'status' => 'required|in:draft,sent,partially_paid,paid,cancelled,overdue',
            'issue_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issue_date',
            'notes' => 'nullable|string',
            'client_id' => 'nullable|exists:clients,id',
            'project_id' => 'nullable|exists:projects,id',
        ]);

        $validated['created_by'] = Auth::id();
        $validated['invoice_number'] = $this->generateInvoiceNumber();

        $invoice = Invoice::create($validated);

        return redirect()->route('invoices.index')->with('success', 'Invoice created successfully.');
    }

    public function show(Invoice $invoice): Response
    {
        $this->authorize('view', $invoice);

        $invoice->load([
            'project',
            'client',
            'creator',
            'activities' => function ($query) {
                $query->with('causer')->latest()->limit(10);
            },
        ]);

        return Inertia::render('invoices/Show', [
            'invoice' => [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'title' => $invoice->title,
                'amount' => $invoice->amount,
                'amount_paid' => $invoice->amount_paid,
                'balance' => $invoice->amount - $invoice->amount_paid,
                'status' => $invoice->status,
                'issue_date' => $invoice->issue_date?->toISOString(),
                'due_date' => $invoice->due_date?->toISOString(),
                'paid_at' => $invoice->paid_at?->toISOString(),
                'notes' => $invoice->notes,
                'created_at' => $invoice->created_at->toISOString(),
                'updated_at' => $invoice->updated_at->toISOString(),
                'client' => $invoice->client ? [
                    'id' => $invoice->client->id,
                    'name' => $invoice->client->name,
                    'email' => $invoice->client->email,
                    'company' => $invoice->client->company,
                ] : null,
                'project' => $invoice->project ? [
                    'id' => $invoice->project->id,
                    'name' => $invoice->project->name,
                ] : null,
                'creator' => $invoice->creator ? [
                    'id' => $invoice->creator->id,
                    'name' => $invoice->creator->name,
                ] : null,
            ],
            'activities' => $invoice->activities->map(fn ($activity) => [
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

    public function edit(Invoice $invoice)
    {
        $this->authorize('update', $invoice);

        return Inertia::render('invoices/Edit', [
            'invoice' => [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
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
            'status' => 'required|in:draft,sent,partially_paid,paid,cancelled,overdue',
            'issue_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issue_date',
            'notes' => 'nullable|string',
            'client_id' => 'nullable|exists:clients,id',
            'project_id' => 'nullable|exists:projects,id',
        ]);

        // Update paid_at if status is paid and amount_paid equals amount
        if ($validated['status'] === 'paid' && $validated['amount_paid'] >= $validated['amount']) {
            $validated['paid_at'] = now();
        }

        $invoice->update($validated);

        return redirect()->route('invoices.show', $invoice->id)->with('success', 'Invoice updated successfully.');
    }

    public function destroy(Invoice $invoice)
    {
        $this->authorize('delete', $invoice);

        $invoice->delete();

        return redirect()->route('invoices.index')->with('success', 'Invoice deleted successfully.');
    }

    public function download(Invoice $invoice)
    {
        $this->authorize('view', $invoice);

        $pdf = PDF::loadView('pdf.invoice', compact('invoice'));

        // Store the PDF as a document
        $pdfPath = 'invoices/' . $invoice->invoice_number . '.pdf';
        Storage::disk('public')->put($pdfPath, $pdf->output());

        // Create document record
        Document::create([
            'title' => 'Invoice ' . $invoice->invoice_number,
            'type' => 'invoice',
            'file_path' => $pdfPath,
            'documentable_type' => Invoice::class,
            'documentable_id' => $invoice->id,
            'uploaded_by' => Auth::id(),
        ]);

        return $pdf->download('invoice-'.$invoice->invoice_number.'.pdf');
    }

    public function send(Invoice $invoice)
    {
        $this->authorize('update', $invoice);

        if (! $invoice->client || ! $invoice->client->email) {
            return back()->with('error', 'Client email is required to send invoice.');
        }

        try {
            // Generate PDF
            $pdf = PDF::loadView('pdf.invoice', compact('invoice'))->setPaper('a4', 'portrait');

            // Store the PDF as a document
            $pdfPath = 'invoices/' . $invoice->invoice_number . '.pdf';
            Storage::disk('public')->put($pdfPath, $pdf->output());

            // Create document record
            Document::create([
                'title' => 'Invoice ' . $invoice->invoice_number,
                'type' => 'invoice',
                'file_path' => $pdfPath,
                'documentable_type' => Invoice::class,
                'documentable_id' => $invoice->id,
                'uploaded_by' => Auth::id(),
            ]);

            // Send email
            Mail::to($invoice->client->email)->send(new InvoiceMail($invoice, $pdf->output()));

            // Update invoice status
            if ($invoice->status === 'draft') {
                $invoice->update(['status' => 'sent']);
            }

            return back()->with('success', 'Invoice sent successfully to '.$invoice->client->email);
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to send invoice: '.$e->getMessage());
        }
    }

    public function markAsPaid(Invoice $invoice)
    {
        $this->authorize('update', $invoice);

        $invoice->update([
            'status' => 'paid',
            'amount_paid' => $invoice->amount,
            'paid_at' => now(),
        ]);

        return back()->with('success', 'Invoice marked as paid.');
    }

    private function generateInvoiceNumber()
    {
        $year = date('Y');
        $month = date('m');
        $lastInvoice = Invoice::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->orderBy('id', 'desc')
            ->first();

        $number = $lastInvoice ? (int) substr($lastInvoice->invoice_number, -4) + 1 : 1;

        return 'INV-'.$year.$month.'-'.str_pad($number, 4, '0', STR_PAD_LEFT);
    }
}