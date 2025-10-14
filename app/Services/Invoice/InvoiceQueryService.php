<?php

namespace App\Services\Invoice;

use App\Models\Invoice;
use Illuminate\Pagination\LengthAwarePaginator;

class InvoiceQueryService
{
    public function getFilteredInvoices(array $filters): LengthAwarePaginator
    {
        return Invoice::query()
            ->with(['project', 'client', 'creator'])
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where('title', 'like', "%{$search}%")
                    ->orWhere('invoice_number', 'like', "%{$search}%");
            })
            ->when($filters['status'] ?? null, function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($filters['date_from'] ?? null, fn ($q, $v) => $q->whereDate('issue_date', '>=', $v))
            ->when($filters['date_to'] ?? null, fn ($q, $v) => $q->whereDate('issue_date', '<=', $v))
            ->latest()
            ->paginate(10);
    }

    public function transformInvoicesForResponse(LengthAwarePaginator $invoices): array
    {
        $transformedInvoices = $invoices->through(fn ($invoice) => [
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

        return [
            'data' => $transformedInvoices->items(),
            'links' => $invoices->linkCollection()->toArray(),
            'meta' => [
                'current_page' => $invoices->currentPage(),
                'last_page' => $invoices->lastPage(),
                'per_page' => $invoices->perPage(),
                'total' => $invoices->total(),
                'from' => $invoices->firstItem(),
                'to' => $invoices->lastItem(),
            ],
        ];
    }

    public function getInvoiceWithRelations(Invoice $invoice): array
    {
        $invoice->load([
            'project',
            'client',
            'creator',
            'activities' => function ($query) {
                $query->with('causer')->latest()->limit(10);
            },
        ]);

        return [
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
        ];
    }
}