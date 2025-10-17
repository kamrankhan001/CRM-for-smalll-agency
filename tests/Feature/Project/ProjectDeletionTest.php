<?php

use App\Models\Project;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin']);
    $this->manager = User::factory()->create(['role' => 'manager']);
    $this->member = User::factory()->create(['role' => 'member']);
});

test('admin can delete any project', function () {
    $project = Project::factory()->create(['created_by' => User::factory()]);

    actingAs($this->admin)
        ->delete(route('projects.destroy', $project))
        ->assertRedirect(route('projects.index'))
        ->assertSessionHas('success', 'Project deleted successfully.');

    assertDatabaseMissing('projects', ['id' => $project->id]);
});

test('manager can delete project they created', function () {
    $project = Project::factory()->create(['created_by' => $this->manager->id]);

    actingAs($this->manager)
        ->delete(route('projects.destroy', $project))
        ->assertRedirect(route('projects.index'))
        ->assertSessionHas('success', 'Project deleted successfully.');

    assertDatabaseMissing('projects', ['id' => $project->id]);
});

test('manager cannot delete project they did not create', function () {
    $project = Project::factory()->create(['created_by' => User::factory()]);

    actingAs($this->manager)
        ->delete(route('projects.destroy', $project))
        ->assertForbidden();

    assertDatabaseHas('projects', ['id' => $project->id]);
});

test('member cannot delete any project', function () {
    $project = Project::factory()->create(['created_by' => $this->member->id]);

    actingAs($this->member)
        ->delete(route('projects.destroy', $project))
        ->assertForbidden();

    assertDatabaseHas('projects', ['id' => $project->id]);
});
