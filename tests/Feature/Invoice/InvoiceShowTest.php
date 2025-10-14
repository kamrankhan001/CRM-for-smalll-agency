<?php

use App\Models\Invoice;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin']);
    $this->manager = User::factory()->create(['role' => 'manager']);
    $this->member = User::factory()->create(['role' => 'member']);
    
    $this->invoice = Invoice::factory()->create();
});

test('admin can view invoice', function () {
    $this->actingAs($this->admin)
        ->get(route('invoices.show', $this->invoice))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('invoices/Show')
            ->has('invoice')
            ->has('activities')
        );
});

test('manager can view invoice', function () {
    $this->actingAs($this->manager)
        ->get(route('invoices.show', $this->invoice))
        ->assertStatus(200);
});

test('member cannot view invoice', function () {
    $this->actingAs($this->member)
        ->get(route('invoices.show', $this->invoice))
        ->assertForbidden();
});