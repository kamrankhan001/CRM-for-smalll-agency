<?php

use App\Models\Client;
use App\Models\User;

beforeEach(function () {
    $this->adminUser = User::factory()->create(['role' => 'admin']);
    $this->managerUser = User::factory()->create(['role' => 'manager']);
    $this->memberUser = User::factory()->create(['role' => 'member']);
    $this->testClient = Client::factory()->create(['created_by' => $this->adminUser->id]);
});

test('admin can delete client successfully', function () {
    $this->actingAs($this->adminUser)
        ->delete(route('clients.destroy', $this->testClient))
        ->assertRedirect(route('clients.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseMissing('clients', ['id' => $this->testClient->id]);
});

test('manager gets 403 forbidden when trying to delete client', function () {
    $this->actingAs($this->managerUser)
        ->delete(route('clients.destroy', $this->testClient))
        ->assertForbidden();

    $this->assertDatabaseHas('clients', ['id' => $this->testClient->id]);
});

test('member gets 403 forbidden when trying to delete client', function () {
    $this->actingAs($this->memberUser)
        ->delete(route('clients.destroy', $this->testClient))
        ->assertForbidden();

    $this->assertDatabaseHas('clients', ['id' => $this->testClient->id]);
});

test('member gets 403 forbidden when trying to delete client they created', function () {
    $memberClient = Client::factory()->create(['created_by' => $this->memberUser->id]);

    $this->actingAs($this->memberUser)
        ->delete(route('clients.destroy', $memberClient))
        ->assertForbidden();

    $this->assertDatabaseHas('clients', ['id' => $memberClient->id]);
});

test('manager gets 403 forbidden when trying to delete client they created', function () {
    $managerClient = Client::factory()->create(['created_by' => $this->managerUser->id]);

    $this->actingAs($this->managerUser)
        ->delete(route('clients.destroy', $managerClient))
        ->assertForbidden();

    $this->assertDatabaseHas('clients', ['id' => $managerClient->id]);
});

test('admin can delete any client', function () {
    $otherUser = User::factory()->create();
    $otherUserClient = Client::factory()->create(['created_by' => $otherUser->id]);

    $this->actingAs($this->adminUser)
        ->delete(route('clients.destroy', $otherUserClient))
        ->assertRedirect(route('clients.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseMissing('clients', ['id' => $otherUserClient->id]);
});
