<?php

use App\Models\Task;
use App\Models\User;
use App\Models\Lead;
use App\Models\Client;
use App\Models\Project;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin']);
    $this->manager = User::factory()->create(['role' => 'manager']);
    $this->member = User::factory()->create(['role' => 'member']);
    $this->otherMember = User::factory()->create(['role' => 'member']);
    
    // Create related models first so factory can find them
    Lead::factory()->create();
    Client::factory()->create();
    Project::factory()->create();
});

test('complete marks task as completed for admin', function () {
    $task = Task::factory()->create(['status' => 'pending']);

    $this->actingAs($this->admin)
        ->put(route('tasks.complete', $task)) // Changed from post to put
        ->assertRedirect(route('tasks.show', $task))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('tasks', [
        'id' => $task->id,
        'status' => 'completed'
    ]);
});

test('complete works for manager with their task', function () {
    $task = Task::factory()->create(['status' => 'pending', 'created_by' => $this->manager->id]);

    $this->actingAs($this->manager)
        ->put(route('tasks.complete', $task)) // Changed from post to put
        ->assertRedirect();

    $this->assertDatabaseHas('tasks', [
        'id' => $task->id,
        'status' => 'completed'
    ]);
});

test('complete forbidden for manager with other managers task', function () {
    $otherManager = User::factory()->create(['role' => 'manager']);
    $task = Task::factory()->create([
        'status' => 'pending', 
        'created_by' => $otherManager->id
    ]);

    $this->actingAs($this->manager)
        ->put(route('tasks.complete', $task)) // Changed from post to put
        ->assertForbidden();
});

test('complete works for member with their assigned task', function () {
    $task = Task::factory()->create([
        'status' => 'pending', 
        'assigned_to' => $this->member->id
    ]);

    $this->actingAs($this->member)
        ->put(route('tasks.complete', $task)) // Changed from post to put
        ->assertRedirect();

    $this->assertDatabaseHas('tasks', [
        'id' => $task->id,
        'status' => 'completed'
    ]);
});

test('complete forbidden for member with other members task', function () {
    $task = Task::factory()->create([
        'status' => 'pending', 
        'assigned_to' => $this->otherMember->id,
        'created_by' => $this->otherMember->id
    ]);

    $this->actingAs($this->member)
        ->put(route('tasks.complete', $task)) // Changed from post to put
        ->assertForbidden();
});