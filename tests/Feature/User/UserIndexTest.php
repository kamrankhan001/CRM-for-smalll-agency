<?php

use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin']);
    $this->manager = User::factory()->create(['role' => 'manager']);
    $this->member = User::factory()->create(['role' => 'member']);
});

test('index page loads successfully for admin', function () {
    User::factory()->count(3)->create();

    $this->actingAs($this->admin)
        ->get(route('users.index'))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('users/Index')
            ->has('users')
            ->has('filters')
        );
});

test('index page forbidden for manager', function () {
    $this->actingAs($this->manager)
        ->get(route('users.index'))
        ->assertForbidden();
});

test('index page forbidden for member', function () {
    $this->actingAs($this->member)
        ->get(route('users.index'))
        ->assertForbidden();
});

test('search filter works correctly', function () {
    $user = User::factory()->create(['name' => 'John Doe', 'email' => 'john@example.com']);

    $this->actingAs($this->admin)
        ->get(route('users.index', ['search' => 'John']))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->where('users.data.0.name', 'John Doe')
        );
});

test('role filter works correctly', function () {
    User::factory()->create(['role' => 'manager']);
    User::factory()->create(['role' => 'member']);

    $this->actingAs($this->admin)
        ->get(route('users.index', ['role' => 'manager']))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->where('users.data.0.role', 'manager')
        );
});

test('date filters work correctly', function () {
    User::factory()->create(['created_at' => now()]);
    User::factory()->create(['created_at' => now()->subDays(10)]);

    $this->actingAs($this->admin)
        ->get(route('users.index', [
            'date_from' => now()->subDay()->format('Y-m-d'),
            'date_to' => now()->addDay()->format('Y-m-d'),
        ]))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->has('users.data', 4) // Should see: admin, manager, member, and today's user
        );
});
