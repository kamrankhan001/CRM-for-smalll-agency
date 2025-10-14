<?php

use App\Models\Note;
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

test('index page loads successfully for admin', function () {
    Note::factory()->create();

    $this->actingAs($this->admin)
        ->get(route('notes.index'))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('notes/Index')
            ->has('notes')
            ->has('users')
            ->has('filters')
        );
});

test('index page loads successfully for manager', function () {
    Note::factory()->create();

    $this->actingAs($this->manager)
        ->get(route('notes.index'))
        ->assertStatus(200);
});

test('index page loads successfully for member', function () {
    Note::factory()->create();

    $this->actingAs($this->member)
        ->get(route('notes.index'))
        ->assertStatus(200);
});

test('search filter works correctly', function () {
    $note = Note::factory()->create(['content' => 'Unique Search Content']);

    $this->actingAs($this->admin)
        ->get(route('notes.index', ['search' => 'Unique Search']))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->where('notes.data.0.content', 'Unique Search Content')
        );
});

test('noteable_type filter works correctly', function () {
    Note::factory()->create(['noteable_type' => 'App\\Models\\Lead']);
    Note::factory()->create(['noteable_type' => 'App\\Models\\Client']);

    $this->actingAs($this->admin)
        ->get(route('notes.index', ['noteable_type' => 'lead']))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->where('notes.data.0.noteable.type', 'Lead')
        );
});

test('user_id filter works correctly', function () {
    $user = User::factory()->create();
    $note = Note::factory()->create(['user_id' => $user->id]);

    $this->actingAs($this->admin)
        ->get(route('notes.index', ['user_id' => $user->id]))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->where('notes.data.0.user.id', $user->id)
        );
});