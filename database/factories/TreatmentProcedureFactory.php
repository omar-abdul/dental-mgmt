<?php

namespace Database\Factories;

use App\Models\FeeItem;
use App\Models\Treatment;
use App\Models\TreatmentProcedure;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TreatmentProcedure>
 */
class TreatmentProcedureFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $feeItem = FeeItem::factory()->create();

        return [
            'treatment_id' => Treatment::factory(),
            'fee_item_id' => $feeItem->id,
            'tooth_fdi' => fake()->optional()->numerify('##'),
            'quantity' => 1,
            'fee_cents' => $feeItem->price_cents,
        ];
    }
}
