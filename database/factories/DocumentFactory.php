<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Document>
 */
class DocumentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $documentableType = Arr::random(['App\\Models\\Lead', 'App\\Models\\Client', 'App\\Models\\Project']);
        $documentableId = $documentableType::inRandomOrder()->first()?->id;

        return [
            'title' => $this->faker->sentence(3),
            'type' => $this->faker->randomElement([
                'proposal',
                'contract',
                'invoice',
                'report',
                'brief',
                'misc',
            ]),
            'file_path' => 'documents/'.$this->faker->uuid().'.pdf',
            'documentable_id' => $documentableId,
            'documentable_type' => $documentableType,
            'uploaded_by' => User::inRandomOrder()->first()?->id,
        ];
    }
}
