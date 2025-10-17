<?php

use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin']);
    $this->manager = User::factory()->create(['role' => 'manager']);
    $this->member = User::factory()->create(['role' => 'member']);
    $this->otherMember = User::factory()->create(['role' => 'member']);
});

test('destroy deletes user successfully for admin', function () {
    $user = User::factory()->create();

    $this->actingAs($this->admin)
        ->delete(route('users.destroy', $user))
        ->assertRedirect(route('users.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseMissing('users', ['id' => $user->id]);
});

test('destroy forbidden for manager', function () {
    $user = User::factory()->create();

    $this->actingAs($this->manager)
        ->delete(route('users.destroy', $user))
        ->assertForbidden();

    $this->assertDatabaseHas('users', ['id' => $user->id]);
});

test('destroy forbidden for member', function () {
    $this->actingAs($this->member)
        ->delete(route('users.destroy', $this->otherMember))
        ->assertForbidden();

    $this->assertDatabaseHas('users', ['id' => $this->otherMember->id]);
});

test('destroy forbidden for user trying to delete themselves', function () {
    $this->actingAs($this->member)
        ->delete(route('users.destroy', $this->member))
        ->assertForbidden();

    $this->assertDatabaseHas('users', ['id' => $this->member->id]);
});

test('destroy forbidden for manager trying to delete admin', function () {
    $adminUser = User::factory()->create(['role' => 'admin']);

    $this->actingAs($this->manager)
        ->delete(route('users.destroy', $adminUser))
        ->assertForbidden();

    $this->assertDatabaseHas('users', ['id' => $adminUser->id]);
});
