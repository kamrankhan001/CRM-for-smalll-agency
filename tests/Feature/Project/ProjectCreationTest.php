<?php

use App\Models\Project;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin']);
    $this->manager = User::factory()->create(['role' => 'manager']);
    $this->member = User::factory()->create(['role' => 'member']);
});

test('create page loads successfully for admin and manager', function () {
    $this->actingAs($this->admin)
        ->get(route('projects.create'))
        ->assertStatus(200);

    $this->actingAs($this->manager)
        ->get(route('projects.create'))
        ->assertStatus(200);
});

test('create page returns 403 for member', function () {
    $this->actingAs($this->member)
        ->get(route('projects.create'))
        ->assertForbidden();
});

test('admin can create project successfully', function () {
    $projectData = [
        'name' => 'Test Project',
        'description' => 'Test Description',
        'status' => 'planning',
    ];

    $this->actingAs($this->admin)
        ->post(route('projects.store'), $projectData)
        ->assertRedirect(route('projects.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('projects', [
        'name' => 'Test Project',
        'created_by' => $this->admin->id,
    ]);
});

test('manager can create project successfully', function () {
    $projectData = [
        'name' => 'Manager Project',
        'description' => 'Manager Description',
        'status' => 'in_progress',
    ];

    $this->actingAs($this->manager)
        ->post(route('projects.store'), $projectData)
        ->assertRedirect(route('projects.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('projects', [
        'name' => 'Manager Project',
        'created_by' => $this->manager->id,
    ]);
});

test('member cannot create project', function () {
    $projectData = [
        'name' => 'Member Project',
        'description' => 'Member Description',
        'status' => 'planning',
    ];

    $this->actingAs($this->member)
        ->post(route('projects.store'), $projectData)
        ->assertForbidden();

    $this->assertDatabaseMissing('projects', [
        'name' => 'Member Project',
    ]);
});

test('store creates project with members and sends notifications', function () {
    $memberUser = User::factory()->create(['role' => 'member']);

    $projectData = [
        'name' => 'Project with Members',
        'description' => 'Test Description',
        'status' => 'planning',
        'members' => [$memberUser->id],
    ];

    $this->actingAs($this->admin)
        ->post(route('projects.store'), $projectData);

    $project = Project::where('name', 'Project with Members')->first();
    $this->assertTrue($project->members->contains($memberUser->id));
});
