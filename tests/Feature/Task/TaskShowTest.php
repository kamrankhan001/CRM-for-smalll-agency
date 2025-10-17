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

test('show page loads successfully for admin', function () {
    $task = Task::factory()->create();

    $this->actingAs($this->admin)
        ->get(route('tasks.show', $task))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('tasks/Show')
            ->has('task')
            ->has('notes')
            ->has('activities')
        );
});

test('show page loads successfully for manager', function () {
    $task = Task::factory()->create();

    $this->actingAs($this->manager)
        ->get(route('tasks.show', $task))
        ->assertStatus(200);
});

test('show page loads successfully for assigned member', function () {
    $task = Task::factory()->create(['assigned_to' => $this->member->id]);

    $this->actingAs($this->member)
        ->get(route('tasks.show', $task))
        ->assertStatus(200);
});

test('show page loads successfully for creator member', function () {
    $task = Task::factory()->create(['created_by' => $this->member->id]);

    $this->actingAs($this->member)
        ->get(route('tasks.show', $task))
        ->assertStatus(200);
});

test('show page forbidden for unauthorized member', function () {
    $task = Task::factory()->create([
        'assigned_to' => $this->otherMember->id,
        'created_by' => $this->otherMember->id,
    ]);

    $this->actingAs($this->member)
        ->get(route('tasks.show', $task))
        ->assertForbidden();
});
