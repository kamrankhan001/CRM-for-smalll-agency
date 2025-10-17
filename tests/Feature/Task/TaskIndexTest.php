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

    // Create related models first so factory can find them
    Lead::factory()->create();
    Client::factory()->create();
    Project::factory()->create();
});

test('index page loads successfully for admin', function () {
    Task::factory()->create();

    $this->actingAs($this->admin)
        ->get(route('tasks.index'))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('tasks/Index')
            ->has('tasks')
            ->has('users')
            ->has('filters')
        );
});

test('index page loads successfully for manager', function () {
    Task::factory()->create();

    $this->actingAs($this->manager)
        ->get(route('tasks.index'))
        ->assertStatus(200);
});

test('index page loads successfully for member', function () {
    Task::factory()->create(['assigned_to' => $this->member->id]);

    $this->actingAs($this->member)
        ->get(route('tasks.index'))
        ->assertStatus(200);
});

test('member sees only assigned or created tasks', function () {
    // Create a different user for the other task
    $otherUser = User::factory()->create(['role' => 'member']);

    // Create tasks with specific assignments
    Task::factory()->create(['assigned_to' => $this->member->id]);
    Task::factory()->create(['created_by' => $this->member->id]);
    Task::factory()->create([
        'assigned_to' => $otherUser->id,
        'created_by' => $otherUser->id,
    ]);

    $this->actingAs($this->member)
        ->get(route('tasks.index'))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('tasks/Index')
            ->has('tasks.data', 2) // Should only see 2 tasks (assigned + created)
        );
});

test('search filter works correctly', function () {
    $task = Task::factory()->create(['title' => 'Unique Search Task']);

    $this->actingAs($this->admin)
        ->get(route('tasks.index', ['search' => 'Unique Search']))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->where('tasks.data.0.title', 'Unique Search Task')
        );
});

test('status filter works correctly', function () {
    Task::factory()->create(['status' => 'pending']);
    Task::factory()->create(['status' => 'completed']);

    $this->actingAs($this->admin)
        ->get(route('tasks.index', ['status' => 'pending']))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->where('tasks.data.0.status', 'pending')
        );
});
