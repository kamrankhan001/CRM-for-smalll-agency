<?php

use App\Models\Document;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin']);
    $this->manager = User::factory()->create(['role' => 'manager']);
    $this->member = User::factory()->create(['role' => 'member']);
    $this->otherMember = User::factory()->create(['role' => 'member']);
    
    // Create related models first so factory can find them
    \App\Models\Lead::factory()->create();
    \App\Models\Client::factory()->create();
    \App\Models\Project::factory()->create();

    Storage::fake('public');
});

test('download works successfully for admin', function () {
    $file = UploadedFile::fake()->create('test.pdf', 1000, 'application/pdf');
    $filePath = $file->store('documents', 'public');
    
    $document = Document::factory()->create([
        'file_path' => $filePath,
        'title' => 'Test Document'
    ]);

    $this->actingAs($this->admin)
        ->get(route('documents.download', $document))
        ->assertOk()
        ->assertHeader('Content-Disposition', 'attachment; filename="Test Document.pdf"');
});

test('download works for uploader', function () {
    $file = UploadedFile::fake()->create('uploader.pdf', 1000, 'application/pdf');
    $filePath = $file->store('documents', 'public');
    
    $document = Document::factory()->create([
        'file_path' => $filePath,
        'uploaded_by' => $this->member->id,
        'title' => 'Uploader Document'
    ]);

    $this->actingAs($this->member)
        ->get(route('documents.download', $document))
        ->assertOk();
});

test('download forbidden for non-uploader member', function () {
    $document = Document::factory()->create(['uploaded_by' => $this->otherMember->id]);

    $this->actingAs($this->member)
        ->get(route('documents.download', $document))
        ->assertForbidden();
});

test('download returns 404 for non-existent file', function () {
    $document = Document::factory()->create([
        'file_path' => 'non-existent-file.pdf',
        'uploaded_by' => $this->admin->id
    ]);

    $this->actingAs($this->admin)
        ->get(route('documents.download', $document))
        ->assertStatus(404);
});