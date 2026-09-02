<?php

namespace Database\Factories;

use App\Enums\InventoryMovementType;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryMovement>
 */
class InventoryMovementFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'inventory_item_id' => InventoryItem::factory(),
            'delta' => fake()->numberBetween(-10, 50),
            'type' => fake()->randomElement(InventoryMovementType::cases()),
            'user_id' => User::factory(),
            'reason' => fake()->optional()->sentence(),
        ];
    }
}
