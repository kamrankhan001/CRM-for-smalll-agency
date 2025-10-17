<?php

use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin']);
    $this->manager = User::factory()->create(['role' => 'manager']);
    $this->member = User::factory()->create(['role' => 'member']);
});

test('create page loads successfully for admin', function () {
    $this->actingAs($this->admin)
        ->get(route('users.create'))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('users/Create')
        );
});

test('create page forbidden for manager', function () {
    $this->actingAs($this->manager)
        ->get(route('users.create'))
        ->assertForbidden();
});

test('create page forbidden for member', function () {
    $this->actingAs($this->member)
        ->get(route('users.create'))
        ->assertForbidden();
});

test('store creates a new user with valid data for admin', function () {
    $userData = [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'manager',
    ];

    $this->actingAs($this->admin)
        ->post(route('users.store'), $userData)
        ->assertRedirect(route('users.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('users', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'role' => 'manager',
    ]);
});

test('store forbidden for manager', function () {
    $userData = [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'member',
    ];

    $this->actingAs($this->manager)
        ->post(route('users.store'), $userData)
        ->assertForbidden();
});

test('store forbidden for member', function () {
    $userData = [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'member',
    ];

    $this->actingAs($this->member)
        ->post(route('users.store'), $userData)
        ->assertForbidden();
});

test('store fails with invalid data', function () {
    $invalidData = [
        'name' => '', // Required field empty
        'email' => 'invalid-email', // Invalid email
        'password' => 'short', // Password too short
        'role' => 'invalid_role', // Invalid role
    ];

    $this->actingAs($this->admin)
        ->post(route('users.store'), $invalidData)
        ->assertSessionHasErrors(['name', 'email', 'password', 'role']);
});

test('store fails with duplicate email', function () {
    $existingUser = User::factory()->create(['email' => 'existing@example.com']);

    $userData = [
        'name' => 'Test User',
        'email' => 'existing@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'member',
    ];

    $this->actingAs($this->admin)
        ->post(route('users.store'), $userData)
        ->assertSessionHasErrors(['email']);
});
