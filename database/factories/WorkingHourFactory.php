<?php

namespace Database\Factories;

use App\Models\WorkingHour;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkingHour>
 */
class WorkingHourFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'weekday' => fake()->numberBetween(0, 6),
            'opens_at' => '08:00:00',
            'closes_at' => '18:00:00',
        ];
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'opens_at' => null,
            'closes_at' => null,
        ]);
    }
}
