<?php

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin']);
    $this->manager = User::factory()->create(['role' => 'manager']);
    $this->member = User::factory()->create(['role' => 'member']);

    $this->client = Client::factory()->create();
    $this->project = Project::factory()->create();
});

test('create page loads successfully for admin', function () {
    $this->actingAs($this->admin)
        ->get(route('invoices.create'))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('invoices/Create')
            ->has('clients')
            ->has('projects')
        );
});

test('create page loads successfully for manager', function () {
    $this->actingAs($this->manager)
        ->get(route('invoices.create'))
        ->assertStatus(200);
});

test('create page redirects for member', function () {
    $this->actingAs($this->member)
        ->get(route('invoices.create'))
        ->assertForbidden();
});

test('admin can create invoice', function () {
    $invoiceData = [
        'title' => 'Test Invoice',
        'amount' => 1000.50,
        'amount_paid' => 0,
        'status' => 'draft',
        'issue_date' => now()->format('Y-m-d'),
        'due_date' => now()->addDays(30)->format('Y-m-d'),
        'notes' => 'Test notes',
        'client_id' => $this->client->id,
        'project_id' => $this->project->id,
    ];

    $this->actingAs($this->admin)
        ->post(route('invoices.store'), $invoiceData)
        ->assertRedirect(route('invoices.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('invoices', [
        'title' => 'Test Invoice',
        'amount' => 1000.50,
        'status' => 'draft',
        'client_id' => $this->client->id,
    ]);
});

test('manager can create invoice', function () {
    $invoiceData = [
        'title' => 'Manager Invoice',
        'amount' => 500.00,
        'amount_paid' => 0,
        'status' => 'draft',
        'issue_date' => now()->format('Y-m-d'),
        'due_date' => now()->addDays(30)->format('Y-m-d'),
    ];

    $this->actingAs($this->manager)
        ->post(route('invoices.store'), $invoiceData)
        ->assertRedirect(route('invoices.index'));

    $this->assertDatabaseHas('invoices', [
        'title' => 'Manager Invoice',
        'amount' => 500.00,
    ]);
});

test('member cannot create invoice', function () {
    $invoiceData = [
        'title' => 'Member Invoice',
        'amount' => 100.00,
        'status' => 'draft',
        'issue_date' => now()->format('Y-m-d'),
        'due_date' => now()->addDays(30)->format('Y-m-d'),
    ];

    $this->actingAs($this->member)
        ->post(route('invoices.store'), $invoiceData)
        ->assertForbidden();
});

test('invoice creation requires valid data', function () {
    $invalidData = [
        'title' => '',
        'amount' => 'invalid',
        'status' => 'invalid_status',
    ];

    $this->actingAs($this->admin)
        ->post(route('invoices.store'), $invalidData)
        ->assertSessionHasErrors(['title', 'amount', 'status', 'issue_date', 'due_date']);
});

test('invoice number is generated automatically', function () {
    $invoiceData = [
        'title' => 'Auto Number Invoice',
        'amount' => 1000.00,
        'amount_paid' => 0,
        'status' => 'draft',
        'issue_date' => now()->format('Y-m-d'),
        'due_date' => now()->addDays(30)->format('Y-m-d'),
    ];

    $this->actingAs($this->admin)
        ->post(route('invoices.store'), $invoiceData);

    $this->assertDatabaseHas('invoices', [
        'title' => 'Auto Number Invoice',
    ]);

    $invoice = Invoice::first();
    expect($invoice->invoice_number)->toMatch('/^INV-\d{6}-\d{4}$/');
});

test('created_by field is set to authenticated user', function () {
    $invoiceData = [
        'title' => 'User Assignment Test',
        'amount' => 750.00,
        'amount_paid' => 0,
        'status' => 'draft',
        'issue_date' => now()->format('Y-m-d'),
        'due_date' => now()->addDays(30)->format('Y-m-d'),
    ];

    $this->actingAs($this->admin)
        ->post(route('invoices.store'), $invoiceData);

    $invoice = Invoice::first();
    expect($invoice->created_by)->toBe($this->admin->id);
});

test('amount_paid cannot exceed amount', function () {
    $invoiceData = [
        'title' => 'Invalid Amount Test',
        'amount' => 100.00,
        'amount_paid' => 150.00, // More than amount
        'status' => 'draft',
        'issue_date' => now()->format('Y-m-d'),
        'due_date' => now()->addDays(30)->format('Y-m-d'),
    ];

    $this->actingAs($this->admin)
        ->post(route('invoices.store'), $invoiceData)
        ->assertSessionHasErrors(['amount_paid']);
});
