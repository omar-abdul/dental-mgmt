<?php

namespace Database\Factories;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Treatment;
use App\Models\User;
use Database\Factories\Concerns\GeneratesPublicNumbers;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    use GeneratesPublicNumbers;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotalCents = fake()->numberBetween(1500, 50000);
        $discountCents = 0;
        $taxCents = 0;
        $totalCents = $subtotalCents - $discountCents + $taxCents;
        $amountPaidCents = 0;
        $balanceCents = $totalCents - $amountPaidCents;

        return [
            'invoice_number' => $this->dcmsPublicNumber('INV'),
            'patient_id' => Patient::factory(),
            'treatment_id' => null,
            'issued_by' => User::factory(),
            'issued_at' => fake()->dateTimeBetween('-30 days', 'now'),
            'status' => InvoiceStatus::Issued,
            'subtotal_cents' => $subtotalCents,
            'discount_cents' => $discountCents,
            'tax_cents' => $taxCents,
            'total_cents' => $totalCents,
            'amount_paid_cents' => $amountPaidCents,
            'balance_cents' => $balanceCents,
        ];
    }

    public function forPatient(Patient $patient): static
    {
        return $this->for($patient);
    }

    public function forTreatment(?Treatment $treatment = null): static
    {
        return $this->state(function (array $attributes) use ($treatment): array {
            $treatment ??= Treatment::factory()->create();

            return [
                'treatment_id' => $treatment->id,
                'patient_id' => $treatment->patient_id,
            ];
        });
    }

    public function withAmounts(
        int $subtotalCents,
        int $discountCents = 0,
        int $taxCents = 0,
        int $amountPaidCents = 0,
    ): static {
        $totalCents = $subtotalCents - $discountCents + $taxCents;
        $balanceCents = $totalCents - $amountPaidCents;

        return $this->state(fn (array $attributes) => [
            'subtotal_cents' => $subtotalCents,
            'discount_cents' => $discountCents,
            'tax_cents' => $taxCents,
            'total_cents' => $totalCents,
            'amount_paid_cents' => $amountPaidCents,
            'balance_cents' => $balanceCents,
        ]);
    }
}
