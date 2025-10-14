<?php

use App\Models\Document;
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
    Document::factory()->create();

    $this->actingAs($this->admin)
        ->get(route('documents.index'))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('documents/Index')
            ->has('documents')
            ->has('users')
            ->has('types')
            ->has('filters')
        );
});

test('index page loads successfully for manager', function () {
    Document::factory()->create();

    $this->actingAs($this->manager)
        ->get(route('documents.index'))
        ->assertStatus(200);
});

test('index page loads successfully for member', function () {
    Document::factory()->create();

    $this->actingAs($this->member)
        ->get(route('documents.index'))
        ->assertStatus(200);
});

test('search filter works correctly', function () {
    $document = Document::factory()->create(['title' => 'Unique Search Document']);

    $this->actingAs($this->admin)
        ->get(route('documents.index', ['search' => 'Unique Search']))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->where('documents.data.0.title', 'Unique Search Document')
        );
});

test('type filter works correctly', function () {
    Document::factory()->create(['type' => 'proposal']);
    Document::factory()->create(['type' => 'contract']);

    $this->actingAs($this->admin)
        ->get(route('documents.index', ['type' => 'proposal']))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->where('documents.data.0.type', 'proposal')
        );
});

test('documentable_type filter works correctly', function () {
    Document::factory()->create(['documentable_type' => 'App\\Models\\Lead']);
    Document::factory()->create(['documentable_type' => 'App\\Models\\Client']);

    $this->actingAs($this->admin)
        ->get(route('documents.index', ['documentable_type' => 'lead']))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->where('documents.data.0.documentable.type', 'Lead')
        );
});