<?php

namespace Database\Factories;

use App\Models\User;
use Carbon\Carbon;
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

        // 30% chance to create an appointment in the current month
        if ($this->faker->boolean(30)) {
            $start = $this->faker->dateTimeBetween(
                Carbon::now()->startOfMonth(),
                Carbon::now()->endOfMonth()
            );
        } else {
            // otherwise anywhere in the past 12 months to next month
            $start = $this->faker->dateTimeBetween('-12 months', '+1 month');
        }

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
            'created_at' => $this->faker->dateTimeBetween('-12 months', 'now'),
            'updated_at' => now(),
        ];
    }
}
