<?php

namespace Database\Factories;

use App\Enums\TreatmentPlanItemAcceptance;
use App\Models\FeeItem;
use App\Models\TreatmentPlan;
use App\Models\TreatmentPlanItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TreatmentPlanItem>
 */
class TreatmentPlanItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'treatment_plan_id' => TreatmentPlan::factory(),
            'fee_item_id' => FeeItem::factory(),
            'description' => fake()->sentence(),
            'tooth_fdi' => fake()->optional()->randomElement(['11', '16', '21', '36']),
            'fee_cents' => fake()->numberBetween(1000, 50000),
            'acceptance_status' => TreatmentPlanItemAcceptance::Proposed,
        ];
    }
}
