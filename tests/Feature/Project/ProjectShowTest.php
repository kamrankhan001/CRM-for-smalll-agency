<?php

use App\Models\Project;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin']);
    $this->manager = User::factory()->create(['role' => 'manager']);
    $this->member = User::factory()->create(['role' => 'member']);
});

test('admin can view any project', function () {
    $project = Project::factory()->create();

    $this->actingAs($this->admin)
        ->get(route('projects.show', $project))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('projects/Show')
            ->has('project')
        );
});

test('user can view project they created', function () {
    $project = Project::factory()->create(['created_by' => $this->member->id]);

    $this->actingAs($this->member)
        ->get(route('projects.show', $project))
        ->assertStatus(200);
});

test('user can view project they are member of', function () {
    $project = Project::factory()->create();
    $project->members()->attach($this->member->id);

    $this->actingAs($this->member)
        ->get(route('projects.show', $project))
        ->assertStatus(200);
});

test('manager can view project they are member of', function () {
    $project = Project::factory()->create();
    $project->members()->attach($this->manager->id);

    $this->actingAs($this->manager)
        ->get(route('projects.show', $project))
        ->assertStatus(200);
});

test('user gets 403 when viewing unrelated project', function () {
    $otherUser = User::factory()->create();
    $project = Project::factory()->create(['created_by' => $otherUser->id]);

    $this->actingAs($this->member)
        ->get(route('projects.show', $project))
        ->assertForbidden();
});

test('manager gets 403 when viewing unrelated project', function () {
    $otherUser = User::factory()->create();
    $project = Project::factory()->create(['created_by' => $otherUser->id]);

    $this->actingAs($this->manager)
        ->get(route('projects.show', $project))
        ->assertForbidden();
});