<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Task>
 */
class TaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $taskableType = Arr::random(['App\\Models\\Lead', 'App\\Models\\Client']);
        $taskableId = $taskableType::inRandomOrder()->first()?->id;

        return [
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'status' => $this->faker->randomElement(['pending', 'in_progress', 'completed']),
            'priority' => $this->faker->randomElement(['low', 'medium', 'high', 'urgent']),
            'due_date' => $this->faker->dateTimeBetween('-11 months', '+1 month'),
            'taskable_id' => $taskableId,
            'taskable_type' => $taskableType,
            'assigned_to' => User::inRandomOrder()->first()?->id,
            'created_by' => User::inRandomOrder()->first()?->id,
            'created_at' => $this->faker->dateTimeBetween('-12 months', 'now'),
            'updated_at' => now(),
        ];
    }
}
