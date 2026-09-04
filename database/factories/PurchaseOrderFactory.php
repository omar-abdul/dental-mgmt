<?php

namespace Database\Factories;

use App\Enums\PurchaseOrderStatus;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseOrder>
 */
class PurchaseOrderFactory extends Factory
{
    protected $model = PurchaseOrder::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'number' => sprintf('PO-%s-%05d', now()->format('Y'), fake()->unique()->numberBetween(1, 99999)),
            'supplier_id' => Supplier::factory(),
            'status' => PurchaseOrderStatus::Pending,
            'notes' => fake()->optional()->sentence(),
            'ordered_at' => now(),
        ];
    }

    public function received(): static
    {
        return $this->state(fn (): array => [
            'status' => PurchaseOrderStatus::Received,
            'received_at' => now(),
        ]);
    }
}
