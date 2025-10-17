<?php

use App\Models\Note;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin']);
    $this->manager = User::factory()->create(['role' => 'manager']);
    $this->member = User::factory()->create(['role' => 'member']);
    $this->otherMember = User::factory()->create(['role' => 'member']);

    // Create related models first so factory can find them
    \App\Models\Lead::factory()->create();
    \App\Models\Client::factory()->create();
    \App\Models\Project::factory()->create();
});

test('show page loads successfully for admin', function () {
    $note = Note::factory()->create();

    $this->actingAs($this->admin)
        ->get(route('notes.show', $note))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('notes/Show')
            ->has('note')
            ->has('activities')
        );
});

test('show page loads successfully for manager', function () {
    $note = Note::factory()->create();

    $this->actingAs($this->manager)
        ->get(route('notes.show', $note))
        ->assertStatus(200);
});

test('show page loads successfully for member', function () {
    $note = Note::factory()->create();

    $this->actingAs($this->member)
        ->get(route('notes.show', $note))
        ->assertStatus(200);
});

test('show page loads for note creator', function () {
    $note = Note::factory()->create(['user_id' => $this->member->id]);

    $this->actingAs($this->member)
        ->get(route('notes.show', $note))
        ->assertStatus(200);
});
