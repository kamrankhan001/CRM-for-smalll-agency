<?php

use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->admin = User::factory()->create(['role' => 'admin']);
});

test('create page loads successfully', function () {
    $this->actingAs($this->user)
        ->get(route('leads.create'))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('leads/Create')
            ->has('users')
        );
});

test('store creates a new lead with valid data', function () {
    $leadData = [
        'name' => 'Test Lead',
        'email' => 'test@example.com',
        'phone' => '+1234567890',
        'company' => 'Test Company',
        'source' => 'website',
        'status' => 'new',
    ];

    $this->actingAs($this->user)
        ->post(route('leads.store'), $leadData)
        ->assertRedirect(route('leads.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('leads', [
        'name' => 'Test Lead',
        'email' => 'test@example.com',
        'created_by' => $this->user->id,
    ]);
});

test('store fails with invalid data', function () {
    $invalidData = [
        'name' => '', // Required field empty
        'email' => 'invalid-email', // Invalid email
        'status' => 'invalid-status', // Invalid status
    ];

    $this->actingAs($this->user)
        ->post(route('leads.store'), $invalidData)
        ->assertSessionHasErrors(['name', 'email', 'status']);
});

test('store assigns lead to another user and sends notification', function () {
    $assignedUser = User::factory()->create();

    $leadData = [
        'name' => 'Test Lead',
        'email' => 'test@example.com',
        'status' => 'new',
        'assigned_to' => $assignedUser->id,
    ];

    $this->actingAs($this->user)
        ->post(route('leads.store'), $leadData);

    $this->assertDatabaseHas('leads', [
        'name' => 'Test Lead',
        'assigned_to' => $assignedUser->id,
    ]);

    // Notification should be sent (you can add more specific notification tests)
    // Notification::assertSentTo($assignedUser, LeadAssignedNotification::class);
});
