<?php

namespace App\Services\Lead;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class LeadQueryService
{
    /**
     * Get filtered and paginated leads with related data
     */
    public function getFilteredLeads(array $filters, User $currentUser): LengthAwarePaginator
    {
        $query = Lead::with(['creator', 'assignee']);

        // Apply search filter
        if (! empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")
                    ->orWhere('email', 'like', "%{$filters['search']}%")
                    ->orWhere('company', 'like', "%{$filters['search']}%");
            });
        }

        // Apply status filter
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Apply assigned_to filter
        if (! empty($filters['assigned_to'])) {
            $query->where('assigned_to', $filters['assigned_to']);
        }

        // Apply date range filters
        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
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

    /**
     * Transform leads for API response
     */
    public function transformLeadsForResponse(LengthAwarePaginator $leads): array
    {
        $transformedLeads = $leads->through(fn ($lead) => [
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

        return [
            'data' => $transformedLeads->items(),
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
        ];
    }
}
