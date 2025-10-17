<?php

use App\Models\Client;
use App\Models\Lead;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin']);
    $this->manager = User::factory()->create(['role' => 'manager']);
    $this->member = User::factory()->create(['role' => 'member']);
    $this->otherMember = User::factory()->create(['role' => 'member']);

    // Create related models first so factory can find them
    $this->lead = Lead::factory()->create();
    $this->client = Client::factory()->create();
    $this->project = Project::factory()->create();
});

test('edit page loads successfully for admin', function () {
    $task = Task::factory()->create();

    $this->actingAs($this->admin)
        ->get(route('tasks.edit', $task))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('tasks/Edit')
            ->has('task')
            ->has('users')
            ->has('leads')
            ->has('clients')
            ->has('projects')
        );
});

test('edit page loads successfully for manager with their task', function () {
    $task = Task::factory()->create(['created_by' => $this->manager->id]);

    $this->actingAs($this->manager)
        ->get(route('tasks.edit', $task))
        ->assertStatus(200);
});

test('edit page forbidden for manager with other managers task', function () {
    $otherManager = User::factory()->create(['role' => 'manager']);
    $task = Task::factory()->create(['created_by' => $otherManager->id]);

    $this->actingAs($this->manager)
        ->get(route('tasks.edit', $task))
        ->assertForbidden();
});

test('edit page loads successfully for member with their assigned task', function () {
    $task = Task::factory()->create(['assigned_to' => $this->member->id]);

    $this->actingAs($this->member)
        ->get(route('tasks.edit', $task))
        ->assertStatus(200);
});

test('edit page forbidden for member with other members task', function () {
    $task = Task::factory()->create([
        'assigned_to' => $this->otherMember->id,
        'created_by' => $this->otherMember->id,
    ]);

    $this->actingAs($this->member)
        ->get(route('tasks.edit', $task))
        ->assertForbidden();
});

test('update works successfully for admin', function () {
    $task = Task::factory()->create();
    $updateData = [
        'title' => 'Updated Title',
        'status' => 'completed',
        'priority' => 'high',
        'taskable_type' => 'lead',
        'taskable_id' => $this->lead->id,
    ];

    $this->actingAs($this->admin)
        ->put(route('tasks.update', $task), $updateData)
        ->assertRedirect(route('tasks.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('tasks', [
        'id' => $task->id,
        'title' => 'Updated Title',
        'status' => 'completed',
    ]);
});

test('update works for manager with their task', function () {
    $task = Task::factory()->create(['created_by' => $this->manager->id]);
    $updateData = [
        'title' => 'Manager Updated',
        'status' => 'in_progress',
        'priority' => 'medium',
        'taskable_type' => 'client',
        'taskable_id' => $this->client->id,
    ];

    $this->actingAs($this->manager)
        ->put(route('tasks.update', $task), $updateData)
        ->assertRedirect();

    $this->assertDatabaseHas('tasks', [
        'id' => $task->id,
        'title' => 'Manager Updated',
    ]);
});

test('update forbidden for manager with other managers task', function () {
    $otherManager = User::factory()->create(['role' => 'manager']);
    $task = Task::factory()->create(['created_by' => $otherManager->id]);

    $this->actingAs($this->manager)
        ->put(route('tasks.update', $task), ['title' => 'Unauthorized Update'])
        ->assertForbidden();
});

test('update works for member with their assigned task', function () {
    $task = Task::factory()->create(['assigned_to' => $this->member->id]);
    $updateData = [
        'title' => 'Member Updated',
        'status' => 'completed',
        'priority' => 'low',
        'taskable_type' => 'project',
        'taskable_id' => $this->project->id,
    ];

    $this->actingAs($this->member)
        ->put(route('tasks.update', $task), $updateData)
        ->assertRedirect();

    $this->assertDatabaseHas('tasks', [
        'id' => $task->id,
        'title' => 'Member Updated',
    ]);
});

test('update forbidden for member with other members task', function () {
    $task = Task::factory()->create([
        'assigned_to' => $this->otherMember->id,
        'created_by' => $this->otherMember->id,
    ]);

    $this->actingAs($this->member)
        ->put(route('tasks.update', $task), ['title' => 'Unauthorized'])
        ->assertForbidden();
});
