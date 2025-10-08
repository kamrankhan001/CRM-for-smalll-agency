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
        // $admin = User::factory()->admin()->create(['name' => 'Admin User']);
        // $manager = User::factory()->manager()->create(['name' => 'Manager User']);
        // $members = User::factory()->count(10)->member()->create();

        // // Create leads and clients
        // $leads = Lead::factory(30)->create();
        // $clients = Client::factory(15)->create();

        // // Create tasks & notes
        // Task::factory(50)->create();
        // Note::factory(60)->create();
        // Activity::factory(100)->create();

        // Notification::factory(30)->create();

        // Document::factory()->count(10)->create();

        Project::factory()->count(5)->create();

        Project::factory()
            ->count(10)
            ->afterCreating(function ($project) {
                // Get some existing users (random 2–3)
                $users = User::inRandomOrder()->take(rand(2, 3))->pluck('id');
                $project->members()->attach($users);
            })
            ->create();

    }
}
