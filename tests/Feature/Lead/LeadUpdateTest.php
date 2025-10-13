<?php

use App\Models\Lead;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->lead = Lead::factory()->create(['created_by' => $this->user->id]);
});

test('edit page loads successfully', function () {
    $this->actingAs($this->user)
        ->get(route('leads.edit', $this->lead))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('leads/Edit')
            ->has('lead')
            ->has('users')
        );
});

test('update modifies lead with valid data', function () {
    $updateData = [
        'name' => 'Updated Lead Name',
        'email' => 'updated@example.com',
        'status' => 'contacted',
    ];

    $this->actingAs($this->user)
        ->put(route('leads.update', $this->lead), $updateData)
        ->assertRedirect(route('leads.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('leads', [
        'id' => $this->lead->id,
        'name' => 'Updated Lead Name',
        'status' => 'contacted',
    ]);
});

test('update converts lead to client when status changes to qualified', function () {
    $updateData = [
        'name' => $this->lead->name,
        'email' => $this->lead->email,
        'status' => 'qualified', // Changing to qualified should trigger conversion
    ];

    $this->actingAs($this->user)
        ->put(route('leads.update', $this->lead), $updateData);

    $this->assertDatabaseHas('clients', [
        'lead_id' => $this->lead->id,
        'name' => $this->lead->name,
    ]);

    $this->assertDatabaseHas('leads', [
        'id' => $this->lead->id,
        'status' => 'qualified',
    ]);
});

test('update fails with invalid data', function () {
    $invalidData = [
        'name' => '', // Required field empty
        'email' => 'invalid-email',
        'status' => 'invalid-status',
    ];

    $this->actingAs($this->user)
        ->put(route('leads.update', $this->lead), $invalidData)
        ->assertSessionHasErrors(['name', 'email', 'status']);
});

test('user cannot update lead they did not create', function () {
    $otherUser = User::factory()->create();
    $lead = Lead::factory()->create(['created_by' => $otherUser->id]);

    $this->actingAs($this->user)
        ->put(route('leads.update', $lead), [
            'name' => 'Updated Name',
            'status' => 'contacted',
        ])
        ->assertForbidden();
});