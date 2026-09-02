<?php

namespace Database\Factories;

use App\Enums\MobileMoneyProvider;
use App\Enums\PaymentMethod;
use App\Enums\VerificationStatus;
use App\Models\MobileMoneyTransaction;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MobileMoneyTransaction>
 */
class MobileMoneyTransactionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'payment_id' => Payment::factory()->state([
                'method' => PaymentMethod::Zaad,
            ]),
            'provider' => MobileMoneyProvider::Telesom,
            'payer_phone' => fake()->numerify('+25263#######'),
            'transaction_id' => 'ZAAD-TXN-'.fake()->unique()->bothify('??####'),
            'reference_number' => fake()->bothify('ZAAD-REF-####'),
            'verification_status' => VerificationStatus::VerificationRequired,
            'verified_by' => null,
            'verified_at' => null,
        ];
    }
}
