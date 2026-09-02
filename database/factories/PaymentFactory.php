<?php

namespace Database\Factories;

use App\Enums\MobileMoneyProvider;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\VerificationStatus;
use App\Models\Invoice;
use App\Models\MobileMoneyTransaction;
use App\Models\Payment;
use App\Models\User;
use Database\Factories\Concerns\GeneratesPublicNumbers;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    use GeneratesPublicNumbers;

    public function configure(): static
    {
        return $this->afterMaking(function (Payment $payment): void {
            if ($payment->invoice_id === null) {
                return;
            }

            $invoicePatientId = Invoice::query()
                ->whereKey($payment->invoice_id)
                ->value('patient_id');

            if ($invoicePatientId !== null) {
                $payment->patient_id = $invoicePatientId;
            }
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'payment_number' => $this->dcmsPublicNumber('PAY'),
            'invoice_id' => Invoice::factory(),
            'amount_cents' => fake()->numberBetween(1500, 50000),
            'method' => PaymentMethod::Cash,
            'status' => PaymentStatus::Completed,
            'paid_at' => fake()->dateTimeBetween('-30 days', 'now'),
            'received_by' => User::factory(),
            'reference_number' => fake()->optional()->bothify('REF-####'),
        ];
    }

    public function forInvoice(Invoice $invoice): static
    {
        return $this->state(fn (array $attributes) => [
            'invoice_id' => $invoice->id,
            'patient_id' => $invoice->patient_id,
            'amount_cents' => $invoice->balance_cents,
        ]);
    }

    public function zaad(): static
    {
        return $this->state(fn (array $attributes) => [
            'method' => PaymentMethod::Zaad,
            'status' => PaymentStatus::Completed,
            'reference_number' => fake()->bothify('ZAAD-REF-####'),
        ])->afterCreating(function (Payment $payment): void {
            MobileMoneyTransaction::factory()->create([
                'payment_id' => $payment->id,
                'provider' => MobileMoneyProvider::Telesom,
                'payer_phone' => fake()->numerify('+25263#######'),
                'transaction_id' => 'ZAAD-TXN-'.fake()->unique()->bothify('??####'),
                'reference_number' => $payment->reference_number ?? fake()->bothify('ZAAD-REF-####'),
                'verification_status' => VerificationStatus::Verified,
                'verified_by' => $payment->received_by,
                'verified_at' => now(),
            ]);
        });
    }
}
