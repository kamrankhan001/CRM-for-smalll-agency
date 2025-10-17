<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => 'member',
            'remember_token' => Str::random(10),
            'two_factor_secret' => Str::random(10),
            'two_factor_recovery_codes' => Str::random(10),
            'two_factor_confirmed_at' => now(),
            'created_at' => fake()->dateTimeBetween('-12 months', 'now'),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the model does not have two-factor authentication configured.
     */
    public function withoutTwoFactor(): static
    {
        return $this->state(fn (array $attributes) => [
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ]);
    }

    public function admin()
    {
        return $this->state(fn () => [
            'role' => 'admin',
            'email' => 'admin@crm.com',
            'created_at' => now()->subMonths(11),
            'updated_at' => now(),
        ]);
    }

    public function manager()
    {
        return $this->state(fn () => [
            'role' => 'manager',
            'email' => fake()->unique()->safeEmail(),
            'created_at' => now()->subMonths(8),
            'updated_at' => now(),
        ]);
    }

    public function member()
    {
        return $this->state(fn () => [
            'role' => 'member',
            'created_at' => fake()->dateTimeBetween('-12 months', 'now'),
            'updated_at' => now(),
        ]);
    }
}
