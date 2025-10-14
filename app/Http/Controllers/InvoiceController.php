<?php

namespace App\Http\Controllers;

use App\Actions\Invoice\CreateInvoiceAction;
use App\Actions\Invoice\DeleteInvoiceAction;
use App\Actions\Invoice\DownloadInvoiceAction;
use App\Actions\Invoice\MarkInvoiceAsPaidAction;
use App\Actions\Invoice\SendInvoiceAction;
use App\Actions\Invoice\UpdateInvoiceAction;
use App\Http\Requests\Invoice\StoreInvoiceRequest;
use App\Http\Requests\Invoice\UpdateInvoiceRequest;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Project;
use App\Services\Invoice\InvoiceQueryService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InvoiceController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private InvoiceQueryService $invoiceQueryService,
        private CreateInvoiceAction $createInvoiceAction,
        private UpdateInvoiceAction $updateInvoiceAction,
        private DeleteInvoiceAction $deleteInvoiceAction,
        private DownloadInvoiceAction $downloadInvoiceAction,
        private SendInvoiceAction $sendInvoiceAction,
        private MarkInvoiceAsPaidAction $markInvoiceAsPaidAction
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Invoice::class);

        $filters = $request->only(['search', 'status', 'date_from', 'date_to']);
        $invoices = $this->invoiceQueryService->getFilteredInvoices($filters);
        $transformedInvoices = $this->invoiceQueryService->transformInvoicesForResponse($invoices);

        return Inertia::render('invoices/Index', [
            'invoices' => $transformedInvoices,
            'filters' => $filters,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Invoice::class);

        return Inertia::render('invoices/Create', [
            'clients' => Client::select('id', 'name', 'email')->get(),
            'projects' => Project::select('id', 'name')->get(),
        ]);
    }

    public function store(StoreInvoiceRequest $request): RedirectResponse
    {
        $this->createInvoiceAction->execute($request->validated(), $request->user());

        return redirect()->route('invoices.index')->with('success', 'Invoice created successfully.');
    }

    public function show(Invoice $invoice): Response
    {
        $this->authorize('view', $invoice);

        $data = $this->invoiceQueryService->getInvoiceWithRelations($invoice);

        return Inertia::render('invoices/Show', $data);
    }

    public function edit(Invoice $invoice): Response
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

    public function update(UpdateInvoiceRequest $request, Invoice $invoice): RedirectResponse
    {
        $this->updateInvoiceAction->execute($invoice, $request->validated());

        return redirect()->route('invoices.index', $invoice->id)->with('success', 'Invoice updated successfully.');
    }

    public function destroy(Invoice $invoice): RedirectResponse
    {
        $this->authorize('delete', $invoice);

        $this->deleteInvoiceAction->execute($invoice);

        return redirect()->route('invoices.index')->with('success', 'Invoice deleted successfully.');
    }

    public function download(Invoice $invoice): StreamedResponse|RedirectResponse
    {
        $this->authorize('view', $invoice);

        try {
            return $this->downloadInvoiceAction->execute($invoice);
        } catch (\Throwable $e) {
            \Log::error('Invoice PDF generation failed', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to generate or download PDF.');
        }
    }

    public function send(Invoice $invoice): RedirectResponse
    {
        $this->authorize('update', $invoice);

        if (!$invoice->client || !$invoice->client->email) {
            return back()->with('error', 'Client email is required to send invoice.');
        }

        try {
            $this->sendInvoiceAction->execute($invoice);

            return back()->with('success', 'Invoice sent successfully to '.$invoice->client->email);
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to send invoice: '.$e->getMessage());
        }
    }

    public function markAsPaid(Invoice $invoice): RedirectResponse
    {
        $this->authorize('update', $invoice);

        $this->markInvoiceAsPaidAction->execute($invoice);

        return back()->with('success', 'Invoice marked as paid.');
    }
}