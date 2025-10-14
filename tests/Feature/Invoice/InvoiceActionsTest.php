<?php

use App\Models\Invoice;
use App\Models\User;
use App\Models\Client;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin']);
    $this->manager = User::factory()->create(['role' => 'manager']);
    $this->member = User::factory()->create(['role' => 'member']);
    
    $this->client = Client::factory()->create(['email' => 'client@example.com']);
    $this->invoice = Invoice::factory()->create([
        'status' => 'draft',
        'client_id' => $this->client->id
    ]);

    Mail::fake();
    Storage::fake('public');
});

test('admin can mark invoice as paid', function () {
    $this->actingAs($this->admin)
        ->put(route('invoices.mark-as-paid', $this->invoice))
        ->assertRedirect()
        ->assertSessionHas('success');

    $this->invoice->refresh();
    expect($this->invoice->status)->toBe('paid')
        ->and($this->invoice->amount_paid)->toBe($this->invoice->amount)
        ->and($this->invoice->paid_at)->not->toBeNull();
});

test('manager can mark invoice as paid', function () {
    $this->actingAs($this->manager)
        ->put(route('invoices.mark-as-paid', $this->invoice))
        ->assertRedirect();

    $this->invoice->refresh();
    expect($this->invoice->status)->toBe('paid');
});

test('member cannot mark invoice as paid', function () {
    $this->actingAs($this->member)
        ->put(route('invoices.mark-as-paid', $this->invoice))
        ->assertForbidden();
});

test('admin can send invoice email', function () {
    $this->actingAs($this->admin)
        ->post(route('invoices.send', $this->invoice))
        ->assertRedirect()
        ->assertSessionHas('success');

    $this->invoice->refresh();
    expect($this->invoice->status)->toBe('sent');

    Mail::assertSent(\App\Mail\InvoiceMail::class, function ($mail) {
        return $mail->hasTo('client@example.com');
    });
});

test('manager can send invoice email', function () {
    $this->actingAs($this->manager)
        ->post(route('invoices.send', $this->invoice))
        ->assertRedirect();

    Mail::assertSent(\App\Mail\InvoiceMail::class);
});

test('member cannot send invoice email', function () {
    $this->actingAs($this->member)
        ->post(route('invoices.send', $this->invoice))
        ->assertForbidden();

    Mail::assertNotSent(\App\Mail\InvoiceMail::class);
});

test('cannot send invoice without client email', function () {
    $invoiceWithoutEmail = Invoice::factory()->create([
        'client_id' => Client::factory()->create(['email' => null])->id
    ]);

    $this->actingAs($this->admin)
        ->post(route('invoices.send', $invoiceWithoutEmail))
        ->assertRedirect()
        ->assertSessionHas('error');
});

test('admin can download invoice pdf', function () {
    $this->actingAs($this->admin)
        ->get(route('invoices.download', $this->invoice))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf');
});

test('manager can download invoice pdf', function () {
    $this->actingAs($this->manager)
        ->get(route('invoices.download', $this->invoice))
        ->assertOk();
});

test('member cannot download invoice pdf', function () {
    $this->actingAs($this->member)
        ->get(route('invoices.download', $this->invoice))
        ->assertForbidden();
});