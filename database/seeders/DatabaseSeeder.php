<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Lead;
use App\Models\Client;
use App\Models\Task;
use App\Models\Note;
use App\Models\Activity;
use App\Models\Notification;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        //Create core users
        $admin = User::factory()->admin()->create(['name' => 'Admin User']);
        $manager = User::factory()->manager()->create(['name' => 'Manager User']);
        $members = User::factory()->count(10)->member()->create();

        // Create leads and clients
        $leads = Lead::factory(30)->create();
        $clients = Client::factory(15)->create();

        // Create tasks & notes
        Task::factory(50)->create();
        Note::factory(60)->create();
        Activity::factory(100)->create();

        Notification::factory(30)->create();
    }
}
