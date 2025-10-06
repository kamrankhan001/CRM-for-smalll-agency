<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\User;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Notification>
 */
class NotificationFactory extends Factory
{     
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
          $type = $this->faker->randomElement([
            'LeadAssigned',
            'TaskAssigned',
            'TaskDueSoon',
            'LeadConverted',
            'NoteAdded',
        ]);

        $user = User::inRandomOrder()->first() ?? User::factory()->create();

        $message = match ($type) {
            'LeadAssigned'   => "A new lead has been assigned to you.",
            'TaskAssigned'   => "A new task has been assigned to you.",
            'TaskDueSoon'    => "A task is due within 24 hours.",
            'LeadConverted'  => "A lead was successfully converted to a client.",
            'NoteAdded'      => "A new note was added to one of your leads or tasks.",
            default          => "You have a new notification.",
        };

        $url = match ($type) {
            'LeadAssigned', 'LeadConverted' => '/leads',
            'TaskAssigned', 'TaskDueSoon'   => '/tasks',
            'NoteAdded'                      => '/notes',
            default                          => '/dashboard',
        };

        return [
            'id'         => Str::uuid()->toString(),
            'type'       => "App\\Notifications\\{$type}Notification",
            'notifiable_type' => User::class,
            'notifiable_id'   => $user->id,
            'data' => [
                'message' => $message,
                'url'     => $url,
            ],
            'read_at' => $this->faker->boolean(70) ? now() : null,
            'created_at' => $this->faker->dateTimeBetween('-2 months', 'now'),
        ];
    }
}
