<?php

namespace Database\Factories;

use App\Models\Encounter;
use App\Models\SoapNote;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SoapNote>
 */
class SoapNoteFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'encounter_id' => Encounter::factory(),
            'subjective' => fake()->optional()->sentence(),
            'objective' => fake()->optional()->sentence(),
            'assessment' => fake()->optional()->sentence(),
            'plan' => fake()->optional()->sentence(),
            'signed_at' => null,
            'signed_by' => null,
        ];
    }

    public function signed(): static
    {
        return $this->state(fn (array $attributes) => [
            'signed_at' => now(),
        ]);
    }
}
