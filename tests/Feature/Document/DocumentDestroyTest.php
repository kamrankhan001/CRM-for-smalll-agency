<?php

use App\Models\Document;
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

test('destroy deletes document successfully for admin', function () {
    $document = Document::factory()->create();

    $this->actingAs($this->admin)
        ->delete(route('documents.destroy', $document))
        ->assertRedirect(route('documents.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseMissing('documents', ['id' => $document->id]);
});

test('destroy deletes document successfully for uploader', function () {
    $document = Document::factory()->create(['uploaded_by' => $this->member->id]);

    $this->actingAs($this->member)
        ->delete(route('documents.destroy', $document))
        ->assertRedirect();

    $this->assertDatabaseMissing('documents', ['id' => $document->id]);
});

test('destroy forbidden for non-uploader non-admin', function () {
    $document = Document::factory()->create(['uploaded_by' => $this->otherMember->id]);

    $this->actingAs($this->member)
        ->delete(route('documents.destroy', $document))
        ->assertForbidden();

    $this->assertDatabaseHas('documents', ['id' => $document->id]);
});