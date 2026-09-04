<?php

namespace Database\Factories;

use App\Enums\InstallmentStatus;
use App\Models\Installment;
use App\Models\PaymentPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Installment>
 */
class InstallmentFactory extends Factory
{
    protected $model = Installment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'payment_plan_id' => PaymentPlan::factory(),
            'amount_cents' => fake()->numberBetween(1000, 20000),
            'due_date' => fake()->dateTimeBetween('now', '+90 days'),
            'status' => InstallmentStatus::Pending,
            'paid_at' => null,
        ];
    }
}
