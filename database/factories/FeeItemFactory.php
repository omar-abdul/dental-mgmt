<?php

namespace Database\Factories;

use App\Models\FeeItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FeeItem>
 */
class FeeItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->lexify('???')),
            'name' => fake()->words(3, true),
            'category' => fake()->randomElement(['Consultation', 'Preventive', 'Restorative']),
            'unit' => fake()->randomElement(['visit', 'procedure', 'tooth']),
            'price_cents' => fake()->numberBetween(1000, 50000),
            'tax_rate_bps' => 0,
            'calendar_color' => fake()->hexColor(),
            'default_duration_minutes' => fake()->randomElement([15, 30, 45, 60, 90]),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
