<?php

namespace Database\Factories;

use App\Enums\PaymentPlanStatus;
use App\Models\Invoice;
use App\Models\PaymentPlan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentPlan>
 */
class PaymentPlanFactory extends Factory
{
    protected $model = PaymentPlan::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'invoice_id' => Invoice::factory(),
            'total_cents' => fake()->numberBetween(5000, 50000),
            'status' => PaymentPlanStatus::Active,
            'created_by' => User::factory(),
        ];
    }

    public function forInvoice(Invoice $invoice): static
    {
        return $this->state(fn (array $attributes) => [
            'invoice_id' => $invoice->id,
            'total_cents' => $invoice->balance_cents,
        ]);
    }
}
