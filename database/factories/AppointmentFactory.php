<?php

namespace Database\Factories;

use App\Models\Appointment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Appointment>
 */
class AppointmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $appointableType = Arr::random(['App\\Models\\Lead', 'App\\Models\\Client', 'App\\Models\\Project']);
        $appointableId = $appointableType::inRandomOrder()->first()?->id;

        $start = $this->faker->dateTimeBetween('now', '+10 days');
        $end = (clone $start)->modify('+1 hour');

        return [
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->sentence(),
            'appointable_id' => $appointableId,
            'appointable_type' => $appointableType,
            'date' => $start->format('Y-m-d'),
            'start_time' => $start->format('H:i:s'),
            'end_time' => $end->format('H:i:s'),
            'status' => Arr::random(['pending', 'confirmed', 'cancelled']),
            'created_by' => User::inRandomOrder()->first()?->id,
        ];
    }
}
