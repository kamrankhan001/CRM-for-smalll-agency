<?php

use App\Models\User;
use App\Models\Lead;
use App\Models\Client;
use App\Models\Project;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin']);
    $this->manager = User::factory()->create(['role' => 'manager']);
    $this->member = User::factory()->create(['role' => 'member']);
    
    // Create related models first so factory can find them
    $this->lead = Lead::factory()->create();
    $this->client = Client::factory()->create();
    $this->project = Project::factory()->create();
});

test('create page loads successfully for admin', function () {
    $this->actingAs($this->admin)
        ->get(route('notes.create'))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('notes/Create')
            ->has('leads')
            ->has('clients')
            ->has('projects')
        );
});

test('create page loads successfully for manager', function () {
    $this->actingAs($this->manager)
        ->get(route('notes.create'))
        ->assertStatus(200);
});

test('create page loads successfully for member', function () {
    $this->actingAs($this->member)
        ->get(route('notes.create'))
        ->assertStatus(200);
});

test('store creates a new note with valid data for admin', function () {
    $noteData = [
        'content' => 'Test Note Content',
        'noteable_type' => 'lead',
        'noteable_id' => $this->lead->id,
    ];

    $this->actingAs($this->admin)
        ->post(route('notes.store'), $noteData)
        ->assertRedirect(route('notes.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('notes', [
        'content' => 'Test Note Content',
        'user_id' => $this->admin->id,
    ]);
});

test('store creates a new note for manager', function () {
    $noteData = [
        'content' => 'Manager Note',
        'noteable_type' => 'client',
        'noteable_id' => $this->client->id,
    ];

    $this->actingAs($this->manager)
        ->post(route('notes.store'), $noteData)
        ->assertRedirect();

    $this->assertDatabaseHas('notes', [
        'content' => 'Manager Note',
        'user_id' => $this->manager->id,
    ]);
});

test('store creates a new note for member', function () {
    $noteData = [
        'content' => 'Member Note',
        'noteable_type' => 'project',
        'noteable_id' => $this->project->id,
    ];

    $this->actingAs($this->member)
        ->post(route('notes.store'), $noteData)
        ->assertRedirect();

    $this->assertDatabaseHas('notes', [
        'content' => 'Member Note',
        'user_id' => $this->member->id,
    ]);
});

test('store fails with invalid data', function () {
    $invalidData = [
        'content' => '', // Required field empty
        'noteable_type' => 'invalid_type', // Invalid type
    ];

    $this->actingAs($this->admin)
        ->post(route('notes.store'), $invalidData)
        ->assertSessionHasErrors(['content', 'noteable_type']);
});

test('store maps noteable_type correctly', function () {
    $noteData = [
        'content' => 'Test Note',
        'noteable_type' => 'lead',
        'noteable_id' => $this->lead->id,
    ];

    $this->actingAs($this->admin)
        ->post(route('notes.store'), $noteData);

    $this->assertDatabaseHas('notes', [
        'content' => 'Test Note',
        'noteable_type' => 'App\\Models\\Lead'
    ]);
});