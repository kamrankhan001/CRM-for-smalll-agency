<?php

use App\Models\Project;
use App\Models\User;
use App\Models\Client;
use App\Models\Lead;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin']);
    $this->manager = User::factory()->create(['role' => 'manager']);
    $this->member = User::factory()->create(['role' => 'member']);
    $this->otherMember = User::factory()->create(['role' => 'member']);
    
    $this->client = Client::factory()->create();
    $this->lead = Lead::factory()->create();
});

test('admin can access edit page for any project', function () {
    $project = Project::factory()->create(['created_by' => $this->otherMember->id]);

    $this->actingAs($this->admin)
        ->get(route('projects.edit', $project))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('projects/Edit')
            ->has('project')
            ->has('clients')
            ->has('leads')
            ->has('users')
        );
});

test('manager can access edit page for project they created', function () {
    $project = Project::factory()->create(['created_by' => $this->manager->id]);

    $this->actingAs($this->manager)
        ->get(route('projects.edit', $project))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('projects/Edit')
            ->has('project')
        );
});

test('manager can access edit page for project they are member of', function () {
    $project = Project::factory()->create(['created_by' => $this->otherMember->id]);
    $project->members()->attach($this->manager->id);

    $this->actingAs($this->manager)
        ->get(route('projects.edit', $project))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('projects/Edit')
            ->has('project')
        );
});

test('manager cannot access edit page for unrelated project', function () {
    $project = Project::factory()->create(['created_by' => $this->otherMember->id]);

    $this->actingAs($this->manager)
        ->get(route('projects.edit', $project))
        ->assertForbidden();
});

test('member can access edit page for project they are member of', function () {
    $project = Project::factory()->create(['created_by' => $this->otherMember->id]);
    $project->members()->attach($this->member->id);

    $this->actingAs($this->member)
        ->get(route('projects.edit', $project))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('projects/Edit')
            ->has('project')
        );
});

test('member cannot access edit page for unrelated project', function () {
    $project = Project::factory()->create(['created_by' => $this->otherMember->id]);

    $this->actingAs($this->member)
        ->get(route('projects.edit', $project))
        ->assertForbidden();
});

test('edit page returns correct project data structure', function () {
    $project = Project::factory()->create([
        'created_by' => $this->admin->id,
        'client_id' => $this->client->id,
        'lead_id' => $this->lead->id,
    ]);
    $project->members()->attach([$this->member->id, $this->manager->id]);

    $this->actingAs($this->admin)
        ->get(route('projects.edit', $project))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('projects/Edit')
            ->where('project.id', $project->id)
            ->where('project.name', $project->name)
            ->where('project.description', $project->description)
            ->where('project.status', $project->status)
            ->where('project.start_date', $project->start_date?->toDateString())
            ->where('project.end_date', $project->end_date?->toDateString())
            ->where('project.client_id', $project->client_id)
            ->where('project.lead_id', $project->lead_id)
            ->where('project.created_by', $project->created_by)
            ->has('project.members', 2)
            ->has('project.creator')
            ->has('project.client')
            ->has('project.lead')
            ->has('clients')
            ->has('leads')
            ->has('users')
        );
});

test('edit page includes clients, leads, and users data', function () {
    $project = Project::factory()->create(['created_by' => $this->admin->id]);

    Client::factory()->count(3)->create();
    Lead::factory()->count(2)->create();
    User::factory()->count(5)->create();

    $this->actingAs($this->admin)
        ->get(route('projects.edit', $project))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('projects/Edit')
            ->has('clients', 4) // 3 created + 1 from beforeEach
            ->has('leads', 3) // 2 created + 1 from beforeEach
            ->has('users', 9) // 5 created + 4 from beforeEach (admin, manager, member, otherMember)
        );
});

test('unauthenticated user cannot access edit page', function () {
    $project = Project::factory()->create();

    $this->get(route('projects.edit', $project))
        ->assertRedirect(route('login'));
});

test('admin can update any project', function () {
    $project = Project::factory()->create(['created_by' => $this->otherMember->id]);
    $updateData = [
        'name' => 'Updated Project Name',
        'description' => 'Updated project description',
        'status' => 'in_progress',
        'start_date' => '2024-01-01',
        'end_date' => '2024-12-31',
        'client_id' => $this->client->id,
        'lead_id' => $this->lead->id,
        'members' => [$this->manager->id, $this->member->id],
    ];

    $this->actingAs($this->admin)
        ->put(route('projects.update', $project), $updateData)
        ->assertRedirect(route('projects.index'))
        ->assertSessionHas('success', 'Project updated successfully.');

    $this->assertDatabaseHas('projects', [
        'id' => $project->id,
        'name' => 'Updated Project Name',
        'description' => 'Updated project description',
        'status' => 'in_progress',
    ]);
});

test('manager can update project they created', function () {
    $project = Project::factory()->create(['created_by' => $this->manager->id]);
    $updateData = [
        'name' => 'Manager Updated Project',
        'status' => 'completed',
    ];

    $this->actingAs($this->manager)
        ->put(route('projects.update', $project), $updateData)
        ->assertRedirect(route('projects.index'))
        ->assertSessionHas('success', 'Project updated successfully.');

    $this->assertDatabaseHas('projects', [
        'id' => $project->id,
        'name' => 'Manager Updated Project',
        'status' => 'completed',
    ]);
});

test('manager can update project they are member of', function () {
    $project = Project::factory()->create(['created_by' => $this->otherMember->id]);
    $project->members()->attach($this->manager->id);
    
    $updateData = [
        'name' => 'Manager Member Updated Project',
        'status' => 'on_hold',
    ];

    $this->actingAs($this->manager)
        ->put(route('projects.update', $project), $updateData)
        ->assertRedirect(route('projects.index'))
        ->assertSessionHas('success', 'Project updated successfully.');

    $this->assertDatabaseHas('projects', [
        'id' => $project->id,
        'name' => 'Manager Member Updated Project',
        'status' => 'on_hold',
    ]);
});

test('manager cannot update unrelated project', function () {
    $project = Project::factory()->create(['created_by' => $this->otherMember->id]);
    $updateData = [
        'name' => 'Unauthorized Update',
    ];

    $this->actingAs($this->manager)
        ->put(route('projects.update', $project), $updateData)
        ->assertForbidden();
});

test('member can update project they are member of', function () {
    $project = Project::factory()->create(['created_by' => $this->otherMember->id]);
    $project->members()->attach($this->member->id);
    
    $updateData = [
        'name' => 'Member Team Updated Project',
        'status' => $project->status,
    ];

    $this->actingAs($this->member)
        ->put(route('projects.update', $project), $updateData)
        ->assertRedirect(route('projects.index'))
        ->assertSessionHas('success', 'Project updated successfully.');

    $this->assertDatabaseHas('projects', [
        'id' => $project->id,
        'name' => 'Member Team Updated Project',
        'status' => $project->status,
    ]);
});

test('member cannot update unrelated project', function () {
    $project = Project::factory()->create(['created_by' => $this->otherMember->id]);
    $updateData = [
        'name' => 'Unauthorized Member Update',
    ];

    $this->actingAs($this->member)
        ->put(route('projects.update', $project), $updateData)
        ->assertForbidden();
});

test('project members are synced when updating', function () {
    $project = Project::factory()->create(['created_by' => $this->admin->id]);
    $newMembers = [$this->manager->id, $this->member->id];

    $updateData = [
        'name' => 'Project with updated members',
        'status' => $project->status, // Include required status
        'members' => $newMembers,
    ];

    $this->actingAs($this->admin)
        ->put(route('projects.update', $project), $updateData)
        ->assertRedirect(route('projects.index')); // Add assertion to ensure request completed

    $project->refresh();
    
    // Debug output to see what's happening
    $actualMembers = $project->members->pluck('id')->sort()->values()->toArray();
    $expectedMembers = collect($newMembers)->sort()->values()->toArray();
    
    // Check count first
    $this->assertCount(count($expectedMembers), $actualMembers);
    
    // Then check exact match
    $this->assertEquals($expectedMembers, $actualMembers);
});

test('update shows error message on failure', function () {
    $project = Project::factory()->create(['created_by' => $this->admin->id]);
    
    // Mock the action to throw an exception
    $mock = $this->mock(\App\Actions\Project\UpdateProjectAction::class);
    $mock->shouldReceive('execute')
        ->andThrow(new \Exception('Database error'));

    $updateData = [
        'name' => 'Failing Project',
        'status' => 'in_progress', // Add required fields
    ];

    $this->actingAs($this->admin)
        ->put(route('projects.update', $project), $updateData)
        ->assertRedirect()
        ->assertSessionHas('error', 'Failed to update project: Database error');
});

test('unauthenticated user cannot update project', function () {
    $project = Project::factory()->create();
    $updateData = ['name' => 'Unauthenticated Update'];

    $this->put(route('projects.update', $project), $updateData)
        ->assertRedirect(route('login'));
});