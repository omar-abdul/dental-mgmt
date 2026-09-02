<?php

namespace Database\Factories;

use App\Models\Patient;
use App\Models\PatientMedication;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PatientMedication>
 */
class PatientMedicationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'label' => fake()->randomElement(['Metformin', 'Amlodipine', 'Ibuprofen', 'Paracetamol']),
            'is_critical' => fake()->boolean(10),
        ];
    }
}
