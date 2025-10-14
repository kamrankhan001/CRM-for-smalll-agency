<?php

namespace App\Http\Controllers;

use App\Actions\Client\CreateClientAction;
use App\Actions\Client\UpdateClientAction;
use App\Actions\Client\DeleteClientAction;
use App\Http\Requests\Client\CreateClientRequest;
use App\Http\Requests\Client\UpdateClientRequest;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Services\Client\ClientQueryService;
use App\Models\Client;
use App\Models\User;
use App\Models\Lead;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ClientController extends Controller
{
    use AuthorizesRequests;
    
    public function __construct(
        private ClientQueryService $clientQueryService,
        private CreateClientAction $createClientAction,
        private UpdateClientAction $updateClientAction,
        private DeleteClientAction $deleteClientAction
    ) {}

    /**
     * Display a listing of the clients with filters and pagination
     */
    public function index(): Response
    {
        $this->authorize('viewAny', Client::class);

        $filters = request()->only(['search', 'assigned_to', 'date_from', 'date_to']);
        $clients = $this->clientQueryService->getFilteredClients($filters, auth()->user());
        $transformedClients = $this->clientQueryService->transformClientsForResponse($clients);

        $users = User::select('id', 'name')->get();

        return Inertia::render('clients/Index', [
            'clients' => $transformedClients,
            'filters' => $filters,
            'users' => $users,
        ]);
    }

    /**
     * Show the form for creating a new client
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
     * Store a newly created client in storage
     */
    public function store(CreateClientRequest $request): RedirectResponse
    {
        try {
            $this->createClientAction->execute($request->validated(), $request->user());

            return redirect()->route('clients.index')
                ->with('success', 'Client created successfully.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to create client: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified client with all related data
     */
    public function show(Client $client): Response
    {
        $this->authorize('view', $client);

        $clientData = $this->clientQueryService->getClientWithRelations($client);

        return Inertia::render('clients/Show', $clientData);
    }

    /**
     * Show the form for editing the specified client
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
     * Update the specified client in storage
     */
    public function update(UpdateClientRequest $request, Client $client): RedirectResponse
    {
        try {
            $this->updateClientAction->execute($client, $request->validated());

            return redirect()->route('clients.index')
                ->with('success', 'Client updated successfully.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to update client: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified client from storage
     */
    public function destroy(Client $client): RedirectResponse
    {
        $this->authorize('delete', $client);

        try {
            $this->deleteClientAction->execute($client);

            return redirect()->route('clients.index')
                ->with('success', 'Client deleted successfully.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to delete client: ' . $e->getMessage());
        }
    }
}