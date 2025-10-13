<?php

use App\Models\Lead;
use App\Models\User;
use App\Models\Client;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->lead = Lead::factory()->create(['created_by' => $this->user->id]);
});

test('convert action transforms lead to client', function () {
    $this->actingAs($this->user)
        ->post(route('leads.convert', $this->lead))
        ->assertRedirect(route('leads.show', $this->lead))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('leads', [
        'id' => $this->lead->id,
        'status' => 'qualified',
    ]);

    $this->assertDatabaseHas('clients', [
        'lead_id' => $this->lead->id,
        'name' => $this->lead->name,
        'email' => $this->lead->email,
    ]);
});

test('convert action fails for already converted lead', function () {
    $client = Client::factory()->create(['lead_id' => $this->lead->id]);

    $this->actingAs($this->user)
        ->post(route('leads.convert', $this->lead))
        ->assertRedirect()
        ->assertSessionHas('error');
});

test('user cannot convert lead they did not create and are not assigned to', function () {
    $otherUser = User::factory()->create();
    $lead = Lead::factory()->create([
        'created_by' => $otherUser->id,
        'assigned_to' => null, // Make sure they're not assigned
    ]);

    $this->actingAs($this->user)
        ->post(route('leads.convert', $lead))
        ->assertForbidden();
});

test('user can convert lead they are assigned to but did not create', function () {
    $otherUser = User::factory()->create();
    $lead = Lead::factory()->create([
        'created_by' => $otherUser->id,
        'assigned_to' => $this->user->id, // This user is assigned
    ]);

    $this->actingAs($this->user)
        ->post(route('leads.convert', $lead))
        ->assertRedirect(route('leads.show', $lead))
        ->assertSessionHas('success');
});

test('admin can convert any lead', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $otherUser = User::factory()->create();
    $lead = Lead::factory()->create(['created_by' => $otherUser->id]);

    $this->actingAs($admin)
        ->post(route('leads.convert', $lead))
        ->assertRedirect(route('leads.show', $lead))
        ->assertSessionHas('success');
});