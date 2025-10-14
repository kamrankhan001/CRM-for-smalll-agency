<?php

use App\Models\Project;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin']);
    $this->manager = User::factory()->create(['role' => 'manager']);
    $this->member = User::factory()->create(['role' => 'member']);
});

test('index page loads successfully for all authenticated users', function () {
    $users = [$this->admin, $this->manager, $this->member];

    foreach ($users as $user) {
        $this->actingAs($user)
            ->get(route('projects.index'))
            ->assertStatus(200);
    }
});

test('index page requires authentication', function () {
    $this->get(route('projects.index'))
        ->assertRedirect(route('login'));
});

test('admin can view all projects', function () {
    Project::factory()->count(3)->create();

    $this->actingAs($this->admin)
        ->get(route('projects.index'))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('projects/Index')
            ->has('projects.data', 3)
        );
});

test('manager can view all projects', function () {
    Project::factory()->count(3)->create();

    $this->actingAs($this->manager)
        ->get(route('projects.index'))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('projects/Index')
            ->has('projects.data', 3)
        );
});

test('member can view all projects they created or are member of', function () {
    $otherUser = User::factory()->create();
    
    // Create projects: 2 by member, 1 with member as member, 1 by other user
    Project::factory()->create(['created_by' => $this->member->id]);
    Project::factory()->create(['created_by' => $this->member->id]);
    
    $memberProject = Project::factory()->create(['created_by' => $otherUser->id]);
    $memberProject->members()->attach($this->member->id);
    
    Project::factory()->create(['created_by' => $otherUser->id]); // Not accessible

    $this->actingAs($this->member)
        ->get(route('projects.index'))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('projects/Index')
            ->has('projects.data', 3) // Should see only 3 projects
        );
});

test('index page applies search filter', function () {
    $project = Project::factory()->create(['name' => 'Test Project']);
    Project::factory()->create(['name' => 'Another Project']);

    $this->actingAs($this->admin)
        ->get(route('projects.index', ['search' => 'Test']))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('projects/Index')
            ->has('projects.data', 1)
            ->where('projects.data.0.name', 'Test Project')
        );
});