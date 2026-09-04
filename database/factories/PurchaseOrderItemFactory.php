<?php

namespace Database\Factories;

use App\Models\InventoryItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseOrderItem>
 */
class PurchaseOrderItemFactory extends Factory
{
    protected $model = PurchaseOrderItem::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'purchase_order_id' => PurchaseOrder::factory(),
            'inventory_item_id' => InventoryItem::factory(),
            'quantity_ordered' => fake()->numberBetween(5, 50),
            'quantity_received' => 0,
            'unit_cost_cents' => fake()->numberBetween(100, 5000),
            'batch_number' => strtoupper(fake()->bothify('BATCH-####')),
            'expiry_date' => now()->addMonths(6)->toDateString(),
        ];
    }
}
