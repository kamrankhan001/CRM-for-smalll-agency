<?php

use App\Models\Invoice;
use App\Models\User;
use App\Models\Client;
use App\Models\Project;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin']);
    $this->manager = User::factory()->create(['role' => 'manager']);
    $this->member = User::factory()->create(['role' => 'member']);
    
    // Create related models for factory
    Client::factory()->create();
    Project::factory()->create();
});

test('index page loads successfully for admin', function () {
    Invoice::factory()->create();

    $this->actingAs($this->admin)
        ->get(route('invoices.index'))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('invoices/Index')
            ->has('invoices')
            ->has('filters')
        );
});

test('index page loads successfully for manager', function () {
    Invoice::factory()->create();

    $this->actingAs($this->manager)
        ->get(route('invoices.index'))
        ->assertStatus(200);
});

test('index page redirects for member', function () {
    $this->actingAs($this->member)
        ->get(route('invoices.index'))
        ->assertForbidden();
});

test('search filter works correctly', function () {
    $invoice = Invoice::factory()->create(['title' => 'Unique Search Invoice']);

    $this->actingAs($this->admin)
        ->get(route('invoices.index', ['search' => 'Unique Search']))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->where('invoices.data.0.title', 'Unique Search Invoice')
        );
});

test('status filter works correctly', function () {
    Invoice::factory()->create(['status' => 'draft']);
    Invoice::factory()->create(['status' => 'paid']);

    $this->actingAs($this->admin)
        ->get(route('invoices.index', ['status' => 'draft']))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->where('invoices.data.0.status', 'draft')
        );
});

test('date range filter works correctly', function () {
    $invoice = Invoice::factory()->create([
        'issue_date' => now()->subDays(5)
    ]);

    $this->actingAs($this->admin)
        ->get(route('invoices.index', [
            'date_from' => now()->subDays(10)->format('Y-m-d'),
            'date_to' => now()->format('Y-m-d')
        ]))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->has('invoices.data', 1)
        );
});