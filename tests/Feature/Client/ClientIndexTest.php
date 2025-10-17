<?php

use App\Models\Client;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->admin = User::factory()->create(['role' => 'admin']);
});

test('index page loads successfully for authenticated users', function () {
    $this->actingAs($this->user)
        ->get(route('clients.index'))
        ->assertStatus(200);
});

test('index page requires authentication', function () {
    $this->get(route('clients.index'))
        ->assertRedirect(route('login'));
});

test('admin can view all clients', function () {
    Client::factory()->count(3)->create();

    $this->actingAs($this->admin)
        ->get(route('clients.index'))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('clients/Index')
            ->has('clients.data', 3)
        );
});

test('member can only view their own or assigned clients due to controller filtering', function () {
    $member = User::factory()->create(['role' => 'member']);
    $otherUser = User::factory()->create();

    // Create clients: 2 by member, 1 assigned to member, 1 by other user (not assigned to member)
    Client::factory()->create(['created_by' => $member->id]);
    Client::factory()->create(['created_by' => $member->id]);
    Client::factory()->create(['assigned_to' => $member->id]);
    Client::factory()->create(['created_by' => $otherUser->id, 'assigned_to' => null]); // This one should NOT be visible

    $this->actingAs($member)
        ->get(route('clients.index'))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('clients/Index')
            ->has('clients.data', 3) // Should see only 3 clients (their own + assigned)
        );
});

test('index page applies search filter', function () {
    $client = Client::factory()->create(['name' => 'Test Client']);
    Client::factory()->create(['name' => 'Another Client']);

    $this->actingAs($this->admin)
        ->get(route('clients.index', ['search' => 'Test']))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('clients/Index')
            ->has('clients.data', 1)
            ->where('clients.data.0.name', 'Test Client')
        );
});

test('index page applies assigned_to filter', function () {
    $user = User::factory()->create();
    Client::factory()->create(['assigned_to' => $user->id]);
    Client::factory()->create(['assigned_to' => null]);

    $this->actingAs($this->admin)
        ->get(route('clients.index', ['assigned_to' => $user->id]))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('clients/Index')
            ->has('clients.data', 1)
            ->where('clients.data.0.assigned_to', $user->id)
        );
});
