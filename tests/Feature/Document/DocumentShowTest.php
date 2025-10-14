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

test('show page loads successfully for admin', function () {
    $document = Document::factory()->create();

    $this->actingAs($this->admin)
        ->get(route('documents.show', $document))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('documents/Show')
            ->has('document')
            ->has('activities')
        );
});

test('show page loads successfully for manager', function () {
    $document = Document::factory()->create();

    $this->actingAs($this->manager)
        ->get(route('documents.show', $document))
        ->assertStatus(200);
});

test('show page loads successfully for uploader', function () {
    $document = Document::factory()->create(['uploaded_by' => $this->member->id]);

    $this->actingAs($this->member)
        ->get(route('documents.show', $document))
        ->assertStatus(200);
});

test('show page forbidden for non-uploader member', function () {
    $document = Document::factory()->create(['uploaded_by' => $this->otherMember->id]);

    $this->actingAs($this->member)
        ->get(route('documents.show', $document))
        ->assertForbidden();
});