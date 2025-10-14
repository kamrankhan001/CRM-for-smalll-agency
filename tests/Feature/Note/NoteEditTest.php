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
    $this->otherMember = User::factory()->create(['role' => 'member']);
    
    // Create related models first so factory can find them
    $this->lead = Lead::factory()->create();
    $this->client = Client::factory()->create();
    $this->project = Project::factory()->create();
});

test('edit page loads successfully for admin', function () {
    $note = Note::factory()->create();

    $this->actingAs($this->admin)
        ->get(route('notes.edit', $note))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('notes/Edit')
            ->has('note')
            ->has('leads')
            ->has('clients')
            ->has('projects')
        );
});

test('edit page loads successfully for note creator', function () {
    $note = Note::factory()->create(['user_id' => $this->manager->id]);

    $this->actingAs($this->manager)
        ->get(route('notes.edit', $note))
        ->assertStatus(200);
});

test('edit page forbidden for non-creator non-admin', function () {
    $note = Note::factory()->create(['user_id' => $this->otherMember->id]);

    $this->actingAs($this->member)
        ->get(route('notes.edit', $note))
        ->assertForbidden();
});

test('update works successfully for admin', function () {
    $note = Note::factory()->create();
    $updateData = [
        'content' => 'Updated Content',
        'noteable_type' => 'client',
        'noteable_id' => $this->client->id,
    ];

    $this->actingAs($this->admin)
        ->put(route('notes.update', $note), $updateData)
        ->assertRedirect(route('notes.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('notes', [
        'id' => $note->id,
        'content' => 'Updated Content',
    ]);
});

test('update works for note creator', function () {
    $note = Note::factory()->create(['user_id' => $this->member->id]);
    $updateData = [
        'content' => 'Creator Updated',
        'noteable_type' => 'project',
        'noteable_id' => $this->project->id,
    ];

    $this->actingAs($this->member)
        ->put(route('notes.update', $note), $updateData)
        ->assertRedirect();

    $this->assertDatabaseHas('notes', [
        'id' => $note->id,
        'content' => 'Creator Updated',
    ]);
});

test('update forbidden for non-creator non-admin', function () {
    $note = Note::factory()->create(['user_id' => $this->otherMember->id]);

    $this->actingAs($this->member)
        ->put(route('notes.update', $note), ['content' => 'Unauthorized Update'])
        ->assertForbidden();
});