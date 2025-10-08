<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

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
            'start_date' => $this->faker->dateTimeBetween('-2 months', 'now'),
            'end_date' => $this->faker->optional(0.6)->dateTimeBetween('now', '+3 months'),
            'created_by' => User::inRandomOrder()->first()?->id,
        ];
    }

}
