<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

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
            'users' => $users, // Pass users for assignee filter
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

        Lead::create($validated);

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
            'lead' => $lead->load(['creator', 'assignee']),
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

        $lead->update($validated);

        return redirect()->route('leads.index')
            ->with('success', 'Lead updated successfully.');
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
}
