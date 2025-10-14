<?php

use App\Models\Document;
use App\Models\User;
use App\Models\Lead;
use App\Models\Client;
use App\Models\Project;
use Illuminate\Http\UploadedFile;

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
    $document = Document::factory()->create();

    $this->actingAs($this->admin)
        ->get(route('documents.edit', $document))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('documents/Edit')
            ->has('document')
            ->has('leads')
            ->has('clients')
            ->has('projects')
            ->has('types')
        );
});

test('edit page loads successfully for uploader', function () {
    $document = Document::factory()->create(['uploaded_by' => $this->manager->id]);

    $this->actingAs($this->manager)
        ->get(route('documents.edit', $document))
        ->assertStatus(200);
});

test('edit page forbidden for non-uploader non-admin', function () {
    $document = Document::factory()->create(['uploaded_by' => $this->otherMember->id]);

    $this->actingAs($this->member)
        ->get(route('documents.edit', $document))
        ->assertForbidden();
});

test('update works successfully for admin', function () {
    $document = Document::factory()->create();
    $updateData = [
        'title' => 'Updated Title',
        'type' => 'contract',
        'documentable_type' => 'client',
        'documentable_id' => $this->client->id,
    ];

    $this->actingAs($this->admin)
        ->put(route('documents.update', $document), $updateData)
        ->assertRedirect(route('documents.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('documents', [
        'id' => $document->id,
        'title' => 'Updated Title',
        'type' => 'contract',
    ]);
});

test('update works for uploader', function () {
    $document = Document::factory()->create(['uploaded_by' => $this->member->id]);
    $updateData = [
        'title' => 'Uploader Updated',
        'type' => 'invoice',
        'documentable_type' => 'project',
        'documentable_id' => $this->project->id,
    ];

    $this->actingAs($this->member)
        ->put(route('documents.update', $document), $updateData)
        ->assertRedirect();

    $this->assertDatabaseHas('documents', [
        'id' => $document->id,
        'title' => 'Uploader Updated',
    ]);
});

test('update forbidden for non-uploader non-admin', function () {
    $document = Document::factory()->create(['uploaded_by' => $this->otherMember->id]);

    $this->actingAs($this->member)
        ->put(route('documents.update', $document), ['title' => 'Unauthorized Update'])
        ->assertForbidden();
});

test('update with file replaces old file', function () {
    $document = Document::factory()->create(['uploaded_by' => $this->admin->id]);
    $newFile = UploadedFile::fake()->create('new_document.pdf', 1000, 'application/pdf');

    $updateData = [
        'title' => 'Updated With File',
        'type' => 'report',
        'file' => $newFile,
        'documentable_type' => 'lead',
        'documentable_id' => $this->lead->id,
    ];

    $this->actingAs($this->admin)
        ->put(route('documents.update', $document), $updateData)
        ->assertRedirect();

    $this->assertDatabaseHas('documents', [
        'id' => $document->id,
        'title' => 'Updated With File',
    ]);
});