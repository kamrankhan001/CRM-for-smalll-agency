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
use App\Models\Appointment;
use Illuminate\Support\Facades\Hash;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
         // === DEMO USERS (for live demo login) ===
        User::factory()->admin()->create([
            'name' => 'Admin User',
            'email' => 'admin@crm.com',
            'password' => Hash::make('password'),
        ]);

        User::factory()->manager()->create([
            'name' => 'Manager User',
            'email' => 'manager@crm.com',
            'password' => Hash::make('password'),
        ]);

        User::factory()->member()->create([
            'name' => 'Member User',
            'email' => 'member@crm.com',
            'password' => Hash::make('password'),
        ]);

        // === Additional realistic team ===
        User::factory()->count(10)->member()->create();
        User::factory()->count(2)->manager()->create();

        // === CRM Data (spread across the past year) ===
        Lead::factory(80)->create();
        Client::factory(40)->create();
        Project::factory(20)->create();
        Task::factory(120)->create();
        Note::factory(100)->create();
        Invoice::factory(40)->create();
        Document::factory(30)->create();
        Notification::factory(80)->create();
        Appointment::factory(60)->create();
        Activity::factory(200)->create();

    }
}
