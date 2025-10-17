<?php

use App\Models\Lead;
use App\Models\User;

beforeEach(function () {
    $this->adminUser = User::factory()->create(['role' => 'admin']);
    $this->regularUser = User::factory()->create();
    $this->testLead = Lead::factory()->create(['created_by' => $this->adminUser->id]);
});

test('admin can delete lead successfully', function () {
    $this->actingAs($this->adminUser)
        ->delete(route('leads.destroy', $this->testLead))
        ->assertRedirect(route('leads.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseMissing('leads', ['id' => $this->testLead->id]);
});

test('regular user cannot delete lead', function () {
    $this->actingAs($this->regularUser)
        ->delete(route('leads.destroy', $this->testLead))
        ->assertForbidden();

    $this->assertDatabaseHas('leads', ['id' => $this->testLead->id]);
});

test('user cannot delete lead they did not create', function () {
    $otherUser = User::factory()->create();
    $otherUserLead = Lead::factory()->create(['created_by' => $otherUser->id]);

    $this->actingAs($this->regularUser)
        ->delete(route('leads.destroy', $otherUserLead))
        ->assertForbidden();

    $this->assertDatabaseHas('leads', ['id' => $otherUserLead->id]);
});

test('admin can delete any lead', function () {
    $otherUser = User::factory()->create();
    $otherUserLead = Lead::factory()->create(['created_by' => $otherUser->id]);

    $this->actingAs($this->adminUser)
        ->delete(route('leads.destroy', $otherUserLead))
        ->assertRedirect(route('leads.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseMissing('leads', ['id' => $otherUserLead->id]);
});
