<?php

use App\Models\Client;
use App\Models\Lead;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\UploadedFile;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin']);
    $this->manager = User::factory()->create(['role' => 'manager']);
    $this->member = User::factory()->create(['role' => 'member']);

    // Create related models first so factory can find them
    $this->lead = Lead::factory()->create();
    $this->client = Client::factory()->create();
    $this->project = Project::factory()->create();
});

test('create page loads successfully for admin', function () {
    $this->actingAs($this->admin)
        ->get(route('documents.create'))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('documents/Create')
            ->has('leads')
            ->has('clients')
            ->has('projects')
            ->has('types')
        );
});

test('create page loads successfully for manager', function () {
    $this->actingAs($this->manager)
        ->get(route('documents.create'))
        ->assertStatus(200);
});

test('create page loads successfully for member', function () {
    $this->actingAs($this->member)
        ->get(route('documents.create'))
        ->assertStatus(200);
});

test('store creates a new document with valid data for admin', function () {
    $file = UploadedFile::fake()->create('document.pdf', 1000, 'application/pdf');

    $documentData = [
        'title' => 'Test Document',
        'type' => 'proposal',
        'file' => $file,
        'documentable_type' => 'lead',
        'documentable_id' => $this->lead->id,
    ];

    $this->actingAs($this->admin)
        ->post(route('documents.store'), $documentData)
        ->assertRedirect(route('documents.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('documents', [
        'title' => 'Test Document',
        'type' => 'proposal',
        'uploaded_by' => $this->admin->id,
    ]);
});

test('store creates a new document for manager', function () {
    $file = UploadedFile::fake()->create('manager_doc.pdf', 1000, 'application/pdf');

    $documentData = [
        'title' => 'Manager Document',
        'type' => 'contract',
        'file' => $file,
        'documentable_type' => 'client',
        'documentable_id' => $this->client->id,
    ];

    $this->actingAs($this->manager)
        ->post(route('documents.store'), $documentData)
        ->assertRedirect();

    $this->assertDatabaseHas('documents', [
        'title' => 'Manager Document',
        'uploaded_by' => $this->manager->id,
    ]);
});

test('store creates a new document for member', function () {
    $file = UploadedFile::fake()->create('member_doc.pdf', 1000, 'application/pdf');

    $documentData = [
        'title' => 'Member Document',
        'type' => 'report',
        'file' => $file,
        'documentable_type' => 'project',
        'documentable_id' => $this->project->id,
    ];

    $this->actingAs($this->member)
        ->post(route('documents.store'), $documentData)
        ->assertRedirect();

    $this->assertDatabaseHas('documents', [
        'title' => 'Member Document',
        'uploaded_by' => $this->member->id,
    ]);
});

test('store fails with invalid data', function () {
    $invalidData = [
        'title' => '', // Required field empty
        'type' => 'invalid_type', // Invalid type
    ];

    $this->actingAs($this->admin)
        ->post(route('documents.store'), $invalidData)
        ->assertSessionHasErrors(['title', 'type', 'file', 'documentable_type', 'documentable_id']);
});

test('store maps documentable_type correctly', function () {
    $file = UploadedFile::fake()->create('test.pdf', 1000, 'application/pdf');

    $documentData = [
        'title' => 'Test Document',
        'type' => 'proposal',
        'file' => $file,
        'documentable_type' => 'lead',
        'documentable_id' => $this->lead->id,
    ];

    $this->actingAs($this->admin)
        ->post(route('documents.store'), $documentData);

    $this->assertDatabaseHas('documents', [
        'title' => 'Test Document',
        'documentable_type' => 'App\\Models\\Lead',
    ]);
});
