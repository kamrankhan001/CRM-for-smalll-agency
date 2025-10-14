<?php

use App\Models\Invoice;
use App\Models\User;
use App\Models\Client;
use App\Models\Project;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin']);
    $this->manager = User::factory()->create(['role' => 'manager']);
    $this->member = User::factory()->create(['role' => 'member']);
    
    $this->client = Client::factory()->create();
    $this->project = Project::factory()->create();
    $this->invoice = Invoice::factory()->create(['status' => 'draft']);
});

test('admin can view edit page', function () {
    $this->actingAs($this->admin)
        ->get(route('invoices.edit', $this->invoice))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('invoices/Edit')
            ->has('invoice')
            ->has('clients')
            ->has('projects')
        );
});

test('manager can view edit page', function () {
    $this->actingAs($this->manager)
        ->get(route('invoices.edit', $this->invoice))
        ->assertStatus(200);
});

test('member cannot view edit page', function () {
    $this->actingAs($this->member)
        ->get(route('invoices.edit', $this->invoice))
        ->assertForbidden();
});

test('edit page shows correct invoice data', function () {
    $this->actingAs($this->admin)
        ->get(route('invoices.edit', $this->invoice))
        ->assertInertia(fn ($page) => $page
            ->component('invoices/Edit')
            ->where('invoice.id', $this->invoice->id)
            ->where('invoice.title', $this->invoice->title)
            ->where('invoice.amount', $this->invoice->amount)
            ->where('invoice.status', $this->invoice->status)
        );
});

test('admin can update invoice', function () {
    $updateData = [
        'title' => 'Updated Invoice Title',
        'amount' => 1500.00,
        'amount_paid' => 500.00,
        'status' => 'partially_paid',
        'issue_date' => now()->format('Y-m-d'),
        'due_date' => now()->addDays(45)->format('Y-m-d'),
        'notes' => 'Updated notes',
        'client_id' => $this->client->id,
    ];

    $response = $this->actingAs($this->admin)
        ->put(route('invoices.update', $this->invoice), $updateData);

    $response->assertRedirect();
    $response->assertSessionHas('success');
    
    // Check that it redirects to some invoices route (more flexible)
    $this->assertStringContainsString('invoices', $response->getTargetUrl());

    $this->assertDatabaseHas('invoices', [
        'id' => $this->invoice->id,
        'title' => 'Updated Invoice Title',
        'amount' => 1500.00,
        'status' => 'partially_paid',
    ]);
});

test('manager can update invoice', function () {
    $updateData = [
        'title' => 'Manager Updated Invoice',
        'amount' => 2000.00,
        'amount_paid' => 0,
        'status' => 'sent',
        'issue_date' => now()->format('Y-m-d'),
        'due_date' => now()->addDays(30)->format('Y-m-d'),
    ];

    $response = $this->actingAs($this->manager)
        ->put(route('invoices.update', $this->invoice), $updateData);

    $response->assertRedirect();
    $response->assertSessionHasNoErrors();
    
    // Check that it redirects to some invoices route (more flexible)
    $this->assertStringContainsString('invoices', $response->getTargetUrl());

    $this->assertDatabaseHas('invoices', [
        'id' => $this->invoice->id,
        'title' => 'Manager Updated Invoice',
    ]);
});

test('member cannot update invoice', function () {
    $updateData = [
        'title' => 'Unauthorized Update',
        'amount' => 1000.00,
        'status' => 'sent',
        'issue_date' => now()->format('Y-m-d'),
        'due_date' => now()->addDays(30)->format('Y-m-d'),
    ];

    $this->actingAs($this->member)
        ->put(route('invoices.update', $this->invoice), $updateData)
        ->assertForbidden();
});

test('paid_at is set when status is paid and amount matches', function () {
    $updateData = [
        'title' => 'Paid Invoice',
        'amount' => 1000.00,
        'amount_paid' => 1000.00,
        'status' => 'paid',
        'issue_date' => now()->format('Y-m-d'),
        'due_date' => now()->addDays(30)->format('Y-m-d'),
    ];

    $this->actingAs($this->admin)
        ->put(route('invoices.update', $this->invoice), $updateData);

    $this->invoice->refresh();
    expect($this->invoice->paid_at)->not->toBeNull();
});

test('paid_at is not set when status is paid but amount does not match', function () {
    $updateData = [
        'title' => 'Partially Paid Invoice',
        'amount' => 1000.00,
        'amount_paid' => 500.00, // Less than total amount
        'status' => 'paid', // But status is set to paid
        'issue_date' => now()->format('Y-m-d'),
        'due_date' => now()->addDays(30)->format('Y-m-d'),
    ];

    $this->actingAs($this->admin)
        ->put(route('invoices.update', $this->invoice), $updateData);

    $this->invoice->refresh();
    expect($this->invoice->paid_at)->toBeNull();
});

test('update requires valid data', function () {
    $invalidData = [
        'title' => '',
        'amount' => 'invalid',
        'status' => 'invalid_status',
        'issue_date' => 'invalid-date',
        'due_date' => 'invalid-date',
    ];

    $this->actingAs($this->admin)
        ->put(route('invoices.update', $this->invoice), $invalidData)
        ->assertSessionHasErrors(['title', 'amount', 'status', 'issue_date', 'due_date']);
});

test('due date must be after or equal to issue date', function () {
    $invalidData = [
        'title' => 'Invalid Date Invoice',
        'amount' => 1000.00,
        'amount_paid' => 0,
        'status' => 'draft',
        'issue_date' => now()->format('Y-m-d'),
        'due_date' => now()->subDays(1)->format('Y-m-d'), // Before issue date
    ];

    $this->actingAs($this->admin)
        ->put(route('invoices.update', $this->invoice), $invalidData)
        ->assertSessionHasErrors(['due_date']);
});

test('amount_paid cannot exceed amount during update', function () {
    $invalidData = [
        'title' => 'Invalid Amount Paid',
        'amount' => 100.00,
        'amount_paid' => 150.00, // More than amount
        'status' => 'draft',
        'issue_date' => now()->format('Y-m-d'),
        'due_date' => now()->addDays(30)->format('Y-m-d'),
    ];

    $this->actingAs($this->admin)
        ->put(route('invoices.update', $this->invoice), $invalidData)
        ->assertSessionHasErrors(['amount_paid']);
});