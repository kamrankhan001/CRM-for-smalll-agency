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
    
    // Create related models first so factory can find them
    Lead::factory()->create();
    Client::factory()->create();
    Project::factory()->create();
});

test('destroy deletes task successfully for admin', function () {
    $task = Task::factory()->create();

    $this->actingAs($this->admin)
        ->delete(route('tasks.destroy', $task))
        ->assertRedirect(route('tasks.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
});

test('destroy forbidden for manager', function () {
    $task = Task::factory()->create(['created_by' => $this->manager->id]);

    $this->actingAs($this->manager)
        ->delete(route('tasks.destroy', $task))
        ->assertForbidden();

    $this->assertDatabaseHas('tasks', ['id' => $task->id]);
});

test('destroy forbidden for member', function () {
    $task = Task::factory()->create(['assigned_to' => $this->member->id]);

    $this->actingAs($this->member)
        ->delete(route('tasks.destroy', $task))
        ->assertForbidden();

    $this->assertDatabaseHas('tasks', ['id' => $task->id]);
});