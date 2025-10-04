<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Lead;
use App\Models\User;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Client>
 */
class ClientFactory extends Factory
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
            'email' => $this->faker->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'company' => $this->faker->company(),
            'address' => $this->faker->address(),
            'lead_id' => Lead::inRandomOrder()->first()?->id,
            'assigned_to' => User::inRandomOrder()->first()?->id,
            'created_by' => User::inRandomOrder()->first()?->id,
        ];
    }
}
