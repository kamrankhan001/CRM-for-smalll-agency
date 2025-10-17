<?php

use App\Models\Lead;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->admin = User::factory()->create(['role' => 'admin']);
});

test('index page loads successfully for authenticated users', function () {
    $this->actingAs($this->user)
        ->get(route('leads.index'))
        ->assertStatus(200);
});

test('index page requires authentication', function () {
    $this->get(route('leads.index'))
        ->assertRedirect(route('login'));
});

test('admin can view all leads', function () {
    Lead::factory()->count(3)->create();

    $this->actingAs($this->admin)
        ->get(route('leads.index'))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('leads/Index')
            ->has('leads.data', 3)
        );
});

test('member can only view their own or assigned leads', function () {
    $member = User::factory()->create(['role' => 'member']);
    $otherUser = User::factory()->create();

    // Create leads: 2 by member, 1 assigned to member, 1 by other user
    Lead::factory()->create(['created_by' => $member->id]);
    Lead::factory()->create(['created_by' => $member->id]);
    Lead::factory()->create(['assigned_to' => $member->id]);
    Lead::factory()->create(['created_by' => $otherUser->id]);

    $this->actingAs($member)
        ->get(route('leads.index'))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('leads/Index')
            ->has('leads.data', 3) // Should see only 3 leads
        );
});

test('index page applies search filter', function () {
    $lead = Lead::factory()->create(['name' => 'Test Lead']);
    Lead::factory()->create(['name' => 'Another Lead']);

    $this->actingAs($this->admin)
        ->get(route('leads.index', ['search' => 'Test']))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('leads/Index')
            ->has('leads.data', 1)
            ->where('leads.data.0.name', 'Test Lead')
        );
});

test('index page applies status filter', function () {
    Lead::factory()->create(['status' => 'new']);
    Lead::factory()->create(['status' => 'contacted']);

    $this->actingAs($this->admin)
        ->get(route('leads.index', ['status' => 'new']))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('leads/Index')
            ->has('leads.data', 1)
            ->where('leads.data.0.status', 'new')
        );
});
