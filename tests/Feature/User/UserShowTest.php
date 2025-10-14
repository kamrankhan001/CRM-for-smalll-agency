<?php

use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin']);
    $this->manager = User::factory()->create(['role' => 'manager']);
    $this->member = User::factory()->create(['role' => 'member']);
    $this->otherMember = User::factory()->create(['role' => 'member']);
});

test('show page loads successfully for admin viewing any user profile', function () {
    $user = User::factory()->create();

    $this->actingAs($this->admin)
        ->get(route('users.show', $user))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('users/Show')
            ->has('user')
            ->has('stats')
            ->has('assigned_clients')
            ->has('assigned_leads')
            ->has('recent_tasks')
            ->has('recent_projects')
            ->has('owned_projects')
            ->has('recent_documents')
        );
});

test('show page loads successfully for user viewing their own profile', function () {
    $this->actingAs($this->member)
        ->get(route('users.show', $this->member))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('users/Show')
            ->has('user')
        );
});

test('show page forbidden for user viewing other user profile', function () {
    $this->actingAs($this->member)
        ->get(route('users.show', $this->otherMember))
        ->assertForbidden();
});

test('show page forbidden for manager viewing other user profile', function () {
    $this->actingAs($this->manager)
        ->get(route('users.show', $this->member))
        ->assertForbidden();
});

test('show page loads for admin viewing manager profile', function () {
    $this->actingAs($this->admin)
        ->get(route('users.show', $this->manager))
        ->assertStatus(200);
});

test('show page loads for admin viewing member profile', function () {
    $this->actingAs($this->admin)
        ->get(route('users.show', $this->member))
        ->assertStatus(200);
});

test('show page loads for admin viewing another admin profile', function () {
    $otherAdmin = User::factory()->create(['role' => 'admin']);
    
    $this->actingAs($this->admin)
        ->get(route('users.show', $otherAdmin))
        ->assertStatus(200);
});

test('manager can view their own profile', function () {
    $this->actingAs($this->manager)
        ->get(route('users.show', $this->manager))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('users/Show')
            ->has('user')
        );
});