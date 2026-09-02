<?php

namespace Database\Factories;

use App\Models\Payment;
use App\Models\Receipt;
use Database\Factories\Concerns\GeneratesPublicNumbers;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Receipt>
 */
class ReceiptFactory extends Factory
{
    use GeneratesPublicNumbers;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'receipt_number' => $this->dcmsPublicNumber('RCT'),
            'payment_id' => Payment::factory(),
        ];
    }
}
