<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Note>
 */
class NoteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $noteableType = Arr::random(['App\\Models\\Lead', 'App\\Models\\Client']);
        $noteableId = $noteableType::inRandomOrder()->first()?->id;

        return [
            'noteable_id' => $noteableId,
            'noteable_type' => $noteableType,
            'user_id' => User::inRandomOrder()->first()?->id,
            'content' => $this->faker->paragraph(),
        ];
    }
}
