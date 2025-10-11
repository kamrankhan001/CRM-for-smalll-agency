<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\Client;
use App\Models\Document;
use App\Models\Lead;
use App\Models\Note;
use App\Models\Notification;
use App\Models\project;
use App\Models\Task;
use App\Models\User;
use App\Models\Invoice;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create core users
        User::factory()->admin()->create(['name' => 'Admin User']);
        User::factory()->manager()->create(['name' => 'Manager User']);
        User::factory()->count(10)->member()->create();

        // Create leads and clients
        Lead::factory(30)->create();
        Client::factory(15)->create();

        // Create tasks & notes
        Task::factory(50)->create();
        Note::factory(60)->create();
        Activity::factory(100)->create();

        Notification::factory(30)->create();

        Project::factory()
            ->count(10)
            ->afterCreating(function ($project) {
                $members = User::inRandomOrder()->take(rand(2, 3))->pluck('id');
                $project->members()->attach($members);

                // Ensure the creator is also a member
                if (! $members->contains($project->created_by)) {
                    $project->members()->attach($project->created_by);
                }
            })
            ->create();

        Invoice::factory()->count(30)->create();

        Document::factory()->count(10)->create();


    }
}
