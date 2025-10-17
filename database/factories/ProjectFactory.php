<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->unique()->sentence(3);

        return [
            'name' => $name,
            'description' => $this->faker->paragraph(),
            'status' => $this->faker->randomElement(['planning', 'in_progress', 'on_hold', 'completed']),
            'start_date' => $this->faker->dateTimeBetween('-12 months', '-1 month'),
            'end_date' => $this->faker->optional(0.7)->dateTimeBetween('-1 month', '+3 months'),
            'created_by' => User::inRandomOrder()->first()?->id,
            'created_at' => $this->faker->dateTimeBetween('-12 months', 'now'),
            'updated_at' => now(),
        ];
    }
}
