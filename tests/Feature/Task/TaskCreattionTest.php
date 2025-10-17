<?php

use App\Models\Client;
use App\Models\Lead;
use App\Models\Project;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin']);
    $this->manager = User::factory()->create(['role' => 'manager']);
    $this->member = User::factory()->create(['role' => 'member']);
});

test('create page loads successfully for admin', function () {
    $this->actingAs($this->admin)
        ->get(route('tasks.create'))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('tasks/Create')
            ->has('users')
            ->has('leads')
            ->has('clients')
            ->has('projects')
        );
});

test('create page loads successfully for manager', function () {
    $this->actingAs($this->manager)
        ->get(route('tasks.create'))
        ->assertStatus(200);
});

test('create page loads successfully for member', function () {
    $this->actingAs($this->member)
        ->get(route('tasks.create'))
        ->assertStatus(200);
});

test('store creates a new task with valid data for admin', function () {
    $lead = Lead::factory()->create();
    $taskData = [
        'title' => 'Test Task',
        'description' => 'Test Description',
        'status' => 'pending',
        'priority' => 'medium',
        'due_date' => '2024-12-31',
        'taskable_type' => 'lead',
        'taskable_id' => $lead->id,
        'assigned_to' => $this->admin->id,
    ];

    $this->actingAs($this->admin)
        ->post(route('tasks.store'), $taskData)
        ->assertRedirect(route('tasks.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('tasks', [
        'title' => 'Test Task',
        'status' => 'pending',
        'created_by' => $this->admin->id,
    ]);
});

test('store creates a new task for manager', function () {
    $client = Client::factory()->create();
    $taskData = [
        'title' => 'Manager Task',
        'status' => 'in_progress',
        'priority' => 'high',
        'taskable_type' => 'client',
        'taskable_id' => $client->id,
    ];

    $this->actingAs($this->manager)
        ->post(route('tasks.store'), $taskData)
        ->assertRedirect();

    $this->assertDatabaseHas('tasks', [
        'title' => 'Manager Task',
        'created_by' => $this->manager->id,
    ]);
});

test('store creates a new task for member', function () {
    $project = Project::factory()->create();
    $taskData = [
        'title' => 'Member Task',
        'status' => 'pending',
        'priority' => 'low',
        'taskable_type' => 'project',
        'taskable_id' => $project->id,
    ];

    $this->actingAs($this->member)
        ->post(route('tasks.store'), $taskData)
        ->assertRedirect();

    $this->assertDatabaseHas('tasks', [
        'title' => 'Member Task',
        'created_by' => $this->member->id,
    ]);
});

test('store fails with invalid data', function () {
    $invalidData = [
        'title' => '', // Required field empty
        'status' => 'invalid_status', // Invalid status
        'priority' => 'invalid_priority', // Invalid priority
    ];

    $this->actingAs($this->admin)
        ->post(route('tasks.store'), $invalidData)
        ->assertSessionHasErrors(['title', 'status', 'priority']);
});

test('store maps taskable_type correctly', function () {
    $lead = Lead::factory()->create();
    $taskData = [
        'title' => 'Test Task',
        'status' => 'pending',
        'priority' => 'medium',
        'taskable_type' => 'lead',
        'taskable_id' => $lead->id,
    ];

    $this->actingAs($this->admin)
        ->post(route('tasks.store'), $taskData);

    $this->assertDatabaseHas('tasks', [
        'title' => 'Test Task',
        'taskable_type' => 'App\\Models\\Lead',
    ]);
});
