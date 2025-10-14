<?php

use App\Models\Activity;
use App\Models\User;
use App\Models\Lead;
use App\Models\Client;
use App\Models\Task;
use App\Models\Note;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin']);
    $this->manager = User::factory()->create(['role' => 'manager']);
    $this->member = User::factory()->create(['role' => 'member']);
    $this->otherMember = User::factory()->create(['role' => 'member']);
    
    // Create related models first so factory can find them
    Lead::factory()->create();
    Client::factory()->create();
    Task::factory()->create();
    Note::factory()->create();
});

test('index page loads successfully for admin', function () {
    Activity::factory()->create();

    $this->actingAs($this->admin)
        ->get(route('activities.index'))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('activities/Index')
            ->has('activities')
            ->has('users')
            ->has('filters')
        );
});

test('index page loads successfully for manager', function () {
    Activity::factory()->create();

    $this->actingAs($this->manager)
        ->get(route('activities.index'))
        ->assertStatus(200);
});

test('index page loads successfully for member', function () {
    Activity::factory()->create();

    $this->actingAs($this->member)
        ->get(route('activities.index'))
        ->assertStatus(200);
});

test('admin sees all activities', function () {
    Activity::factory()->count(3)->create();

    $this->actingAs($this->admin)
        ->get(route('activities.index'))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->has('activities.data', 3)
        );
});

test('manager does not see admin activities', function () {
    $adminUser = User::factory()->create(['role' => 'admin']);
    $managerUser = User::factory()->create(['role' => 'manager']);
    
    Activity::factory()->create(['causer_id' => $adminUser->id]);
    Activity::factory()->create(['causer_id' => $managerUser->id]);
    Activity::factory()->create(['causer_id' => $this->member->id]);

    $this->actingAs($this->manager)
        ->get(route('activities.index'))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->has('activities.data', 2) // Should not see admin activity
        );
});

test('member sees only their own activities', function () {
    Activity::factory()->create(['causer_id' => $this->member->id]);
    Activity::factory()->create(['causer_id' => $this->otherMember->id]);
    Activity::factory()->create(['causer_id' => $this->admin->id]);

    $this->actingAs($this->member)
        ->get(route('activities.index'))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->has('activities.data', 1) // Should only see their own activity
        );
});

test('user filter works correctly', function () {
    $user = User::factory()->create(['name' => 'John Doe']);
    Activity::factory()->create(['causer_id' => $user->id]);

    $this->actingAs($this->admin)
        ->get(route('activities.index', ['user' => 'John']))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->where('activities.data.0.causer.name', 'John Doe')
        );
});

test('model filter works correctly', function () {
    Activity::factory()->create(['subject_type' => 'App\\Models\\Lead']);
    Activity::factory()->create(['subject_type' => 'App\\Models\\Client']);

    $this->actingAs($this->admin)
        ->get(route('activities.index', ['model' => 'Lead']))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->where('activities.data.0.model_type', 'Lead')
        );
});

test('action filter works correctly', function () {
    Activity::factory()->create(['action' => 'created']);
    Activity::factory()->create(['action' => 'updated']);

    $this->actingAs($this->admin)
        ->get(route('activities.index', ['action' => 'created']))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->where('activities.data.0.action', 'created')
        );
});

test('date filters work correctly', function () {
    $todayActivity = Activity::factory()->create(['created_at' => now()]);
    $oldActivity = Activity::factory()->create(['created_at' => now()->subDays(10)]);

    $this->actingAs($this->admin)
        ->get(route('activities.index', [
            'date_from' => now()->subDay()->format('Y-m-d'),
            'date_to' => now()->addDay()->format('Y-m-d')
        ]))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->has('activities.data', 1) // Should only see today's activity
        );
});