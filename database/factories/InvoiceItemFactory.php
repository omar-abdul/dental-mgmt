<?php

namespace Database\Factories;

use App\Models\FeeItem;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InvoiceItem>
 */
class InvoiceItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = fake()->numberBetween(1, 3);
        $unitPriceCents = fake()->numberBetween(1000, 20000);
        $discountCents = 0;
        $taxCents = 0;
        $lineTotalCents = ($quantity * $unitPriceCents) - $discountCents + $taxCents;

        return [
            'invoice_id' => Invoice::factory(),
            'fee_item_id' => FeeItem::factory(),
            'description' => fake()->sentence(3),
            'quantity' => $quantity,
            'unit_price_cents' => $unitPriceCents,
            'discount_cents' => $discountCents,
            'tax_cents' => $taxCents,
            'line_total_cents' => $lineTotalCents,
        ];
    }
}
