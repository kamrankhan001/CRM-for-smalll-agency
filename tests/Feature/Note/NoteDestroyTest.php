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

test('destroy deletes note successfully for admin', function () {
    $note = Note::factory()->create();

    $this->actingAs($this->admin)
        ->delete(route('notes.destroy', $note))
        ->assertRedirect(route('notes.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseMissing('notes', ['id' => $note->id]);
});

test('destroy deletes note successfully for creator', function () {
    $note = Note::factory()->create(['user_id' => $this->member->id]);

    $this->actingAs($this->member)
        ->delete(route('notes.destroy', $note))
        ->assertRedirect();

    $this->assertDatabaseMissing('notes', ['id' => $note->id]);
});

test('destroy forbidden for non-creator non-admin', function () {
    $note = Note::factory()->create(['user_id' => $this->otherMember->id]);

    $this->actingAs($this->member)
        ->delete(route('notes.destroy', $note))
        ->assertForbidden();

    $this->assertDatabaseHas('notes', ['id' => $note->id]);
});