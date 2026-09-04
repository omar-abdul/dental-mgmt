<?php

namespace Database\Factories;

use App\Models\InventoryBatch;
use App\Models\InventoryItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryBatch>
 */
class InventoryBatchFactory extends Factory
{
    protected $model = InventoryBatch::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'inventory_item_id' => InventoryItem::factory(),
            'batch_number' => strtoupper(fake()->bothify('BATCH-####')),
            'quantity' => fake()->numberBetween(1, 100),
            'expiry_date' => now()->addMonths(fake()->numberBetween(1, 24))->toDateString(),
        ];
    }

    public function expired(): static
    {
        return $this->state(fn (): array => [
            'expiry_date' => now()->subDay()->toDateString(),
        ]);
    }

    public function expiringSoon(): static
    {
        return $this->state(fn (): array => [
            'expiry_date' => now()->addDays(7)->toDateString(),
        ]);
    }
}
