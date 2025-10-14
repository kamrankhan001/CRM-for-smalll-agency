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

test('show page loads successfully for admin', function () {
    $activity = Activity::factory()->create();

    $this->actingAs($this->admin)
        ->get(route('activities.show', $activity))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('activities/Show')
            ->has('activity')
        );
});

test('show page loads successfully for manager viewing non-admin activity', function () {
    $activity = Activity::factory()->create(['causer_id' => $this->manager->id]);

    $this->actingAs($this->manager)
        ->get(route('activities.show', $activity))
        ->assertStatus(200);
});

test('show page loads successfully for member viewing their own activity', function () {
    $activity = Activity::factory()->create(['causer_id' => $this->member->id]);

    $this->actingAs($this->member)
        ->get(route('activities.show', $activity))
        ->assertStatus(200);
});

test('show page forbidden for manager viewing admin activity', function () {
    $adminUser = User::factory()->create(['role' => 'admin']);
    $activity = Activity::factory()->create(['causer_id' => $adminUser->id]);

    $this->actingAs($this->manager)
        ->get(route('activities.show', $activity))
        ->assertForbidden();
});

test('show page forbidden for member viewing other member activity', function () {
    $activity = Activity::factory()->create(['causer_id' => $this->otherMember->id]);

    $this->actingAs($this->member)
        ->get(route('activities.show', $activity))
        ->assertForbidden();
});

test('show page forbidden for member viewing admin activity', function () {
    $adminUser = User::factory()->create(['role' => 'admin']);
    $activity = Activity::factory()->create(['causer_id' => $adminUser->id]);

    $this->actingAs($this->member)
        ->get(route('activities.show', $activity))
        ->assertForbidden();
});

test('show page displays activity with subject relations', function () {
    $activity = Activity::factory()->create();

    $this->actingAs($this->admin)
        ->get(route('activities.show', $activity))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('activities/Show')
            ->has('activity.description')
            ->has('activity.action')
            ->has('activity.changes')
            ->has('activity.causer')
        );
});