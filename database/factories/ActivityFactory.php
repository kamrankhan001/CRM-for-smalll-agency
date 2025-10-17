<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Lead;
use App\Models\Note;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Activity>
 */
class ActivityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Randomly pick one of the subject types
        $subjects = [
            Lead::class,
            Client::class,
            Task::class,
            Note::class,
        ];

        $subjectType = $this->faker->randomElement($subjects);
        $subject = $subjectType::inRandomOrder()->first();

        // Define some common CRM-style actions
        $actions = ['created', 'updated', 'deleted', 'assigned', 'commented'];

        $action = $this->faker->randomElement($actions);

        return [
            'causer_id' => User::inRandomOrder()->first()?->id,
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => $subject?->id,
            'description' => match ($action) {
                'created' => "Created a new {$this->getShortName($subjectType)}",
                'updated' => "Updated {$this->getShortName($subjectType)} details",
                'deleted' => "Deleted a {$this->getShortName($subjectType)} record",
                'assigned' => "Assigned {$this->getShortName($subjectType)} to a member",
                'commented' => "Added a note to {$this->getShortName($subjectType)}",
                default => null,
            },
            'changes' => [
                'from' => ['status' => $this->faker->randomElement(['new', 'contacted', 'in_progress'])],
                'to' => ['status' => $this->faker->randomElement(['qualified', 'lost', 'converted'])],
            ],
            'created_at' => $this->faker->dateTimeBetween('-12 months', 'now'),
            'updated_at' => now(),
        ];
    }

    /**
     * Get a simple class short name for display purposes
     */
    protected function getShortName(string $class): string
    {
        return strtolower(class_basename($class));
    }
}
