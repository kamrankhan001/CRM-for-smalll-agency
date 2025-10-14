<?php

namespace App\Services\Client;

use App\Models\Client;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class ClientQueryService
{
    public function getFilteredClients(array $filters, User $currentUser): LengthAwarePaginator
    {
        $query = Client::with(['lead', 'creator', 'assignee']);

        // Apply search filter
        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")
                  ->orWhere('email', 'like', "%{$filters['search']}%")
                  ->orWhere('company', 'like', "%{$filters['search']}%");
            });
        }

        // Apply assigned_to filter
        if (!empty($filters['assigned_to'])) {
            $query->where('assigned_to', $filters['assigned_to']);
        }

        // Apply date range filters
        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        // Apply role-based restrictions
        if ($currentUser->role === 'member') {
            $query->where(function ($q) use ($currentUser) {
                $q->where('created_by', $currentUser->id)
                  ->orWhere('assigned_to', $currentUser->id);
            });
        }

        return $query->latest()->paginate(10);
    }

    public function transformClientsForResponse(LengthAwarePaginator $clients): array
    {
        $transformedClients = $clients->through(fn ($client) => [
            'id' => $client->id,
            'name' => $client->name,
            'email' => $client->email,
            'phone' => $client->phone,
            'company' => $client->company,
            'address' => $client->address,
            'lead' => $client->lead ? [
                'id' => $client->lead->id,
                'name' => $client->lead->name,
            ] : null,
            'assignee' => $client->assignee ? [
                'id' => $client->assignee->id,
                'name' => $client->assignee->name,
            ] : null,
            'creator' => $client->creator ? [
                'id' => $client->creator->id,
                'name' => $client->creator->name,
            ] : null,
            'created_by' => $client->created_by,
            'assigned_to' => $client->assigned_to,
            'created_at' => $client->created_at->toDateString(),
            'updated_at' => $client->updated_at->toDateString(),
        ]);

        return [
            'data' => $transformedClients->items(),
            'meta' => [
                'current_page' => $clients->currentPage(),
                'last_page' => $clients->lastPage(),
                'per_page' => $clients->perPage(),
                'total' => $clients->total(),
                'from' => $clients->firstItem(),
                'to' => $clients->lastItem(),
                'prev_page_url' => $clients->previousPageUrl(),
                'next_page_url' => $clients->nextPageUrl(),
            ],
            'links' => $clients->linkCollection()->toArray(),
        ];
    }

    public function getClientWithRelations(Client $client): array
    {
        $client->load([
            'assignee',
            'creator',
            'lead',
            'projects' => function ($query) {
                $query->latest()->limit(10);
            },
            'invoices' => function ($query) {
                $query->latest()->limit(10);
            },
            'notes' => function ($query) {
                $query->with('user')->latest()->limit(10);
            },
            'activities' => function ($query) {
                $query->with('causer')->latest()->limit(10);
            }
        ]);

        return [
            'client' => [
                'id' => $client->id,
                'name' => $client->name,
                'email' => $client->email,
                'phone' => $client->phone,
                'company' => $client->company,
                'address' => $client->address,
                'industry' => $client->industry,
                'revenue' => $client->revenue,
                'status' => $client->status,
                'lead_id' => $client->lead_id,
                'assigned_to' => $client->assigned_to,
                'created_by' => $client->created_by,
                'created_at' => $client->created_at->toISOString(),
                'updated_at' => $client->updated_at->toISOString(),
                'assignee' => $client->assignee ? [
                    'id' => $client->assignee->id,
                    'name' => $client->assignee->name,
                ] : null,
                'creator' => $client->creator ? [
                    'id' => $client->creator->id,
                    'name' => $client->creator->name,
                ] : null,
                'lead' => $client->lead ? [
                    'id' => $client->lead->id,
                    'name' => $client->lead->name,
                ] : null,
            ],
            'projects' => $client->projects->map(fn ($project) => [
                'id' => $project->id,
                'name' => $project->name,
                'description' => $project->description,
                'status' => $project->status,
                'start_date' => $project->start_date?->toISOString(),
                'end_date' => $project->end_date?->toISOString(),
                'created_at' => $project->created_at->toISOString(),
            ]),
            'invoices' => $client->invoices->map(fn ($invoice) => [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'amount' => $invoice->amount,
                'status' => $invoice->status,
                'due_date' => $invoice->due_date?->toISOString(),
                'created_at' => $invoice->created_at->toISOString(),
            ]),
            'notes' => $client->notes->map(fn ($note) => [
                'id' => $note->id,
                'content' => $note->content,
                'user' => [
                    'id' => $note->user->id,
                    'name' => $note->user->name,
                ],
                'created_at' => $note->created_at->toISOString(),
            ]),
            'activities' => $client->activities->map(fn ($activity) => [
                'id' => $activity->id,
                'description' => $activity->description,
                'causer' => $activity->causer ? [
                    'id' => $activity->causer->id,
                    'name' => $activity->causer->name,
                ] : null,
                'created_at' => $activity->created_at->toISOString(),
            ]),
        ];
    }
}