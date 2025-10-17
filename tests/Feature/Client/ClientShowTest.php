<?php

use App\Models\Client;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->client = Client::factory()->create(['created_by' => $this->user->id]);
});

test('show page loads successfully', function () {
    $this->actingAs($this->user)
        ->get(route('clients.show', $this->client))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('clients/Show')
            ->has('client')
            ->has('projects')
            ->has('invoices')
            ->has('notes')
            ->has('activities')
        );
});

test('show page requires authentication', function () {
    $this->get(route('clients.show', $this->client))
        ->assertRedirect(route('login'));
});

test('user cannot view client they did not create and are not assigned to', function () {
    $otherUser = User::factory()->create();
    $client = Client::factory()->create([
        'created_by' => $otherUser->id,
        'assigned_to' => null,
    ]);

    $this->actingAs($this->user)
        ->get(route('clients.show', $client))
        ->assertForbidden();
});

test('user can view client they are assigned to but did not create', function () {
    $otherUser = User::factory()->create();
    $client = Client::factory()->create([
        'created_by' => $otherUser->id,
        'assigned_to' => $this->user->id,
    ]);

    $this->actingAs($this->user)
        ->get(route('clients.show', $client))
        ->assertStatus(200);
});

test('admin can view any client', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $otherUser = User::factory()->create();
    $client = Client::factory()->create(['created_by' => $otherUser->id]);

    $this->actingAs($admin)
        ->get(route('clients.show', $client))
        ->assertStatus(200);
});
