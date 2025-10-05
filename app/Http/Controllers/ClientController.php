<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClientController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of the clients.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Client::class);

        $clients = Client::with(['lead', 'creator', 'assignee'])
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('company', 'like', "%{$search}%");
                });
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
                // Members only see their own or assigned clients
                $query->where('created_by', $request->user()->id)
                    ->orWhere('assigned_to', $request->user()->id);
            })
            ->latest()
            ->paginate(10)
            ->through(fn ($client) => [
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

        $users = User::select('id', 'name')->get();

        return Inertia::render('clients/Index', [
            'clients' => [
                'data' => $clients->items(),
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
            ],
            'filters' => $request->only(['search', 'assigned_to', 'date_from', 'date_to']),
            'users' => $users,
        ]);
    }

    // ... rest of your methods remain the same
    /**
     * Show the form for creating a new client.
     */
    public function create(): Response
    {
        $this->authorize('create', Client::class);

        $users = User::select('id', 'name')->get();
        $leads = Lead::select('id', 'name')->get();

        return Inertia::render('clients/Create', [
            'users' => $users,
            'leads' => $leads,
        ]);
    }

    /**
     * Store a newly created client in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create', Client::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:20',
            'company' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'lead_id' => 'nullable|exists:leads,id',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $validated['created_by'] = $request->user()->id;

        Client::create($validated);

        return redirect()->route('clients.index')
            ->with('success', 'Client created successfully.');
    }

    /**
     * Show the form for editing the specified client.
     */
    public function edit(Client $client): Response
    {
        $this->authorize('update', $client);

        $users = User::select('id', 'name')->get();
        $leads = Lead::select('id', 'name')->get();

        return Inertia::render('clients/Edit', [
            'client' => [
                'id' => $client->id,
                'name' => $client->name,
                'email' => $client->email,
                'phone' => $client->phone,
                'company' => $client->company,
                'address' => $client->address,
                'lead_id' => $client->lead_id,
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
            ],
            'users' => $users,
            'leads' => $leads,
        ]);
    }

    /**
     * Update the specified client in storage.
     */
    public function update(Request $request, Client $client)
    {
        $this->authorize('update', $client);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:20',
            'company' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'lead_id' => 'nullable|exists:leads,id',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $client->update($validated);

        return redirect()->route('clients.index')
            ->with('success', 'Client updated successfully.');
    }

    /**
     * Remove the specified client from storage.
     */
    public function destroy(Client $client)
    {
        $this->authorize('delete', $client);

        $client->delete();

        return redirect()->route('clients.index')
            ->with('success', 'Client deleted successfully.');
    }
}
