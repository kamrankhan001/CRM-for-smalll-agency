<?php

use App\Models\User;
use App\Models\Lead;

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('create page loads successfully', function () {
    $this->actingAs($this->user)
        ->get(route('clients.create'))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('clients/Create')
            ->has('users')
            ->has('leads')
        );
});

test('store creates a new client with valid data', function () {
    $clientData = [
        'name' => 'Test Client',
        'email' => 'test@example.com',
        'phone' => '+1234567890',
        'company' => 'Test Company',
        'address' => 'Test Address',
    ];

    $this->actingAs($this->user)
        ->post(route('clients.store'), $clientData)
        ->assertRedirect(route('clients.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('clients', [
        'name' => 'Test Client',
        'email' => 'test@example.com',
        'created_by' => $this->user->id,
    ]);
});

test('store creates client with lead association', function () {
    $lead = Lead::factory()->create();

    $clientData = [
        'name' => 'Test Client',
        'email' => 'test@example.com',
        'lead_id' => $lead->id,
    ];

    $this->actingAs($this->user)
        ->post(route('clients.store'), $clientData);

    $this->assertDatabaseHas('clients', [
        'name' => 'Test Client',
        'lead_id' => $lead->id,
    ]);
});

test('store fails with invalid data', function () {
    $invalidData = [
        'name' => '', // Required field empty
        'email' => 'invalid-email', // Invalid email
    ];

    $this->actingAs($this->user)
        ->post(route('clients.store'), $invalidData)
        ->assertSessionHasErrors(['name', 'email']);
});

test('store assigns client to another user', function () {
    $assignedUser = User::factory()->create();

    $clientData = [
        'name' => 'Test Client',
        'email' => 'test@example.com',
        'assigned_to' => $assignedUser->id,
    ];

    $this->actingAs($this->user)
        ->post(route('clients.store'), $clientData);

    $this->assertDatabaseHas('clients', [
        'name' => 'Test Client',
        'assigned_to' => $assignedUser->id,
    ]);
});