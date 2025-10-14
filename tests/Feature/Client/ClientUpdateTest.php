<?php

use App\Models\Client;
use App\Models\User;
use App\Models\Lead;

beforeEach(function () {
    $this->adminUser = User::factory()->create(['role' => 'admin']);
    $this->managerUser = User::factory()->create(['role' => 'manager']);
    $this->memberUser = User::factory()->create(['role' => 'member']);
    $this->client = Client::factory()->create(['created_by' => $this->adminUser->id]);
});

test('edit page loads successfully for authorized users', function () {
    $this->actingAs($this->adminUser)
        ->get(route('clients.edit', $this->client))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('clients/Edit')
            ->has('client')
            ->has('users')
            ->has('leads')
        );
});

test('admin can update any client', function () {
    $updateData = [
        'name' => 'Updated Client Name',
        'email' => 'updated@example.com',
        'company' => 'Updated Company',
    ];

    $this->actingAs($this->adminUser)
        ->put(route('clients.update', $this->client), $updateData)
        ->assertRedirect(route('clients.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('clients', [
        'id' => $this->client->id,
        'name' => 'Updated Client Name',
        'email' => 'updated@example.com',
    ]);
});

test('manager can update any client', function () {
    $updateData = [
        'name' => 'Updated by Manager',
        'email' => 'manager@example.com',
    ];

    $this->actingAs($this->managerUser)
        ->put(route('clients.update', $this->client), $updateData)
        ->assertRedirect(route('clients.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('clients', [
        'id' => $this->client->id,
        'name' => 'Updated by Manager',
    ]);
});

test('member can update client they created', function () {
    $memberClient = Client::factory()->create(['created_by' => $this->memberUser->id]);
    
    $updateData = [
        'name' => 'Updated by Member Owner',
        'email' => 'member@example.com',
    ];

    $this->actingAs($this->memberUser)
        ->put(route('clients.update', $memberClient), $updateData)
        ->assertRedirect(route('clients.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('clients', [
        'id' => $memberClient->id,
        'name' => 'Updated by Member Owner',
    ]);
});

test('member can update client they are assigned to', function () {
    $assignedClient = Client::factory()->create([
        'created_by' => $this->adminUser->id,
        'assigned_to' => $this->memberUser->id,
    ]);
    
    $updateData = [
        'name' => 'Updated by Assigned Member',
        'email' => 'assigned@example.com',
    ];

    $this->actingAs($this->memberUser)
        ->put(route('clients.update', $assignedClient), $updateData)
        ->assertRedirect(route('clients.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('clients', [
        'id' => $assignedClient->id,
        'name' => 'Updated by Assigned Member',
    ]);
});

test('member gets 403 forbidden when trying to update unrelated client', function () {
    $unrelatedClient = Client::factory()->create([
        'created_by' => $this->adminUser->id,
        'assigned_to' => null, // Not assigned to member
    ]);

    $this->actingAs($this->memberUser)
        ->put(route('clients.update', $unrelatedClient), [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ])
        ->assertForbidden(); // Fixed: Your app returns 403 (correct behavior)

    // Client should not be updated
    $this->assertDatabaseHas('clients', [
        'id' => $unrelatedClient->id,
        'name' => $unrelatedClient->name, // Original name unchanged
    ]);
});

test('update changes client lead association', function () {
    $newLead = Lead::factory()->create();

    $updateData = [
        'name' => $this->client->name,
        'email' => $this->client->email,
        'lead_id' => $newLead->id,
    ];

    $this->actingAs($this->adminUser)
        ->put(route('clients.update', $this->client), $updateData);

    $this->assertDatabaseHas('clients', [
        'id' => $this->client->id,
        'lead_id' => $newLead->id,
    ]);
});

test('update fails with invalid data', function () {
    $invalidData = [
        'name' => '', // Required field empty
        'email' => 'invalid-email',
    ];

    $this->actingAs($this->adminUser)
        ->put(route('clients.update', $this->client), $invalidData)
        ->assertSessionHasErrors(['name', 'email']);
});