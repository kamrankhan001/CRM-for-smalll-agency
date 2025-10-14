<?php

use App\Models\Activity;
use App\Models\User;
use App\Models\Lead;
use App\Models\Client;
use App\Models\Task;
use App\Models\Note;
use Mockery\MockInterface;
use App\Actions\Activity\DeleteActivityAction;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin']);
    $this->manager = User::factory()->create(['role' => 'manager']);
    $this->member = User::factory()->create(['role' => 'member']);
    $this->otherMember = User::factory()->create(['role' => 'member']);

    // Create related models so factories work
    Lead::factory()->create();
    Client::factory()->create();
    Task::factory()->create();
    Note::factory()->create();
});

test('destroy deletes activity successfully for admin', function () {
    $activity = Activity::factory()->create();

    $this->actingAs($this->admin)
        ->delete(route('activities.destroy', $activity))
        ->assertRedirect()
        ->assertSessionHas('success', 'Activity deleted successfully.');

    $this->assertDatabaseMissing('activities', ['id' => $activity->id]);
});

test('destroy returns 403 for unauthorized manager', function () {
    $activity = Activity::factory()->create(['causer_id' => $this->manager->id]);

    $this->actingAs($this->manager)
        ->delete(route('activities.destroy', $activity))
        ->assertForbidden();

    $this->assertDatabaseHas('activities', ['id' => $activity->id]);
});

test('destroy returns 403 for unauthorized member', function () {
    $activity = Activity::factory()->create(['causer_id' => $this->member->id]);

    $this->actingAs($this->member)
        ->delete(route('activities.destroy', $activity))
        ->assertForbidden();

    $this->assertDatabaseHas('activities', ['id' => $activity->id]);
});

test('destroy returns 403 for manager deleting admin activity', function () {
    $adminUser = User::factory()->create(['role' => 'admin']);
    $activity = Activity::factory()->create(['causer_id' => $adminUser->id]);

    $this->actingAs($this->manager)
        ->delete(route('activities.destroy', $activity))
        ->assertForbidden();

    $this->assertDatabaseHas('activities', ['id' => $activity->id]);
});

test('destroy returns 403 for member deleting another member’s activity', function () {
    $activity = Activity::factory()->create(['causer_id' => $this->otherMember->id]);

    $this->actingAs($this->member)
        ->delete(route('activities.destroy', $activity))
        ->assertForbidden();

    $this->assertDatabaseHas('activities', ['id' => $activity->id]);
});

test('destroy handles errors gracefully for admin', function () {
    $activity = Activity::factory()->create();

    // Mock the action to throw an exception
    $this->mock(DeleteActivityAction::class, function (MockInterface $mock) {
        $mock->shouldReceive('execute')
            ->once()
            ->andThrow(new \Exception('Database error'));
    });

    $this->actingAs($this->admin)
        ->delete(route('activities.destroy', $activity))
        ->assertRedirect()
        ->assertSessionHas('error', 'Failed to delete activity: Database error');

    $this->assertDatabaseHas('activities', ['id' => $activity->id]);
});
