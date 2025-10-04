<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Lead>
 */
class LeadFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'company' => $this->faker->company(),
            'source' => $this->faker->randomElement(['Website', 'Referral', 'Social Media']),
            'status' => $this->faker->randomElement(['new', 'contacted', 'qualified', 'lost']),
            'assigned_to' => User::inRandomOrder()->first()?->id,
            'created_by' => User::inRandomOrder()->first()?->id,
        ];
    }
}
