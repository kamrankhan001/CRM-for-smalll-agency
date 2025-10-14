<?php

use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin']);
    $this->manager = User::factory()->create(['role' => 'manager']);
    $this->member = User::factory()->create(['role' => 'member']);
    $this->otherMember = User::factory()->create(['role' => 'member']);
});

test('edit page loads successfully for admin', function () {
    $user = User::factory()->create();

    $this->actingAs($this->admin)
        ->get(route('users.edit', $user))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('users/Edit')
            ->has('user')
        );
});

test('edit page loads successfully for user editing their own profile', function () {
    $this->actingAs($this->member)
        ->get(route('users.edit', $this->member))
        ->assertStatus(200);
});

test('edit page forbidden for user editing other user profile', function () {
    $this->actingAs($this->member)
        ->get(route('users.edit', $this->otherMember))
        ->assertForbidden();
});

test('edit page forbidden for manager editing other user profile', function () {
    $this->actingAs($this->manager)
        ->get(route('users.edit', $this->member))
        ->assertForbidden();
});

test('update works successfully for admin', function () {
    $user = User::factory()->create();
    $updateData = [
        'name' => 'Updated Name',
        'email' => 'updated@example.com',
        'role' => 'manager',
    ];

    $this->actingAs($this->admin)
        ->put(route('users.update', $user), $updateData)
        ->assertRedirect(route('users.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'name' => 'Updated Name',
        'email' => 'updated@example.com',
        'role' => 'manager',
    ]);
});

test('update works for user updating their own profile', function () {
    $updateData = [
        'name' => 'My Updated Name',
        'email' => $this->member->email, // Keep same email
        'role' => 'member', // Role should remain the same
    ];

    $this->actingAs($this->member)
        ->put(route('users.update', $this->member), $updateData)
        ->assertRedirect();

    $this->assertDatabaseHas('users', [
        'id' => $this->member->id,
        'name' => 'My Updated Name',
    ]);
});

test('update forbidden for user updating other user profile', function () {
    $updateData = [
        'name' => 'Unauthorized Update',
        'email' => $this->otherMember->email,
        'role' => 'member',
    ];

    $this->actingAs($this->member)
        ->put(route('users.update', $this->otherMember), $updateData)
        ->assertForbidden();
});

test('update with password change works', function () {
    $updateData = [
        'name' => 'Test User',
        'email' => $this->member->email,
        'password' => 'newpassword',
        'password_confirmation' => 'newpassword',
        'role' => 'member',
    ];

    $this->actingAs($this->member)
        ->put(route('users.update', $this->member), $updateData)
        ->assertRedirect();

    $this->assertDatabaseHas('users', [
        'id' => $this->member->id,
        'name' => 'Test User',
    ]);
});

test('update fails with invalid data', function () {
    $invalidData = [
        'name' => '',
        'email' => 'invalid-email',
        'role' => 'invalid_role',
    ];

    $this->actingAs($this->admin)
        ->put(route('users.update', $this->member), $invalidData)
        ->assertSessionHasErrors(['name', 'email', 'role']);
});