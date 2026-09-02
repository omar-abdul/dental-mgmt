<?php

namespace Database\Factories;

use App\Enums\InventoryCategory;
use App\Models\InventoryItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryItem>
 */
class InventoryItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'category' => fake()->randomElement(InventoryCategory::cases()),
            'quantity' => fake()->numberBetween(0, 500),
            'unit' => fake()->randomElement(['box', 'pack', 'bottle', 'piece']),
            'reorder_level' => fake()->numberBetween(5, 50),
            'unit_cost_cents' => fake()->numberBetween(100, 10000),
        ];
    }
}
