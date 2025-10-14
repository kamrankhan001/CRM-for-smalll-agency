<?php

use App\Models\Invoice;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin']);
    $this->manager = User::factory()->create(['role' => 'manager']);
    $this->member = User::factory()->create(['role' => 'member']);
    
    $this->invoice = Invoice::factory()->create();
});

test('admin can delete invoice', function () {
    $this->actingAs($this->admin)
        ->delete(route('invoices.destroy', $this->invoice))
        ->assertRedirect(route('invoices.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseMissing('invoices', [
        'id' => $this->invoice->id
    ]);
});

test('manager cannot delete invoice', function () {
    $this->actingAs($this->manager)
        ->delete(route('invoices.destroy', $this->invoice))
        ->assertForbidden();

    $this->assertDatabaseHas('invoices', [
        'id' => $this->invoice->id
    ]);
});

test('member cannot delete invoice', function () {
    $this->actingAs($this->member)
        ->delete(route('invoices.destroy', $this->invoice))
        ->assertForbidden();

    $this->assertDatabaseHas('invoices', [
        'id' => $this->invoice->id
    ]);
});