<?php

namespace Database\Factories;

use App\Models\Patient;
use App\Models\Prescription;
use App\Models\Treatment;
use App\Models\User;
use Database\Factories\Concerns\GeneratesPublicNumbers;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Prescription>
 */
class PrescriptionFactory extends Factory
{
    use GeneratesPublicNumbers;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'number' => $this->dcmsPublicNumber('RX'),
            'treatment_id' => Treatment::factory(),
            'patient_id' => Patient::factory(),
            'prescriber_id' => User::factory()->dentist(),
            'prescribed_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ];
    }
}
