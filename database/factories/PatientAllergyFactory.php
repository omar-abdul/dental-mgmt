<?php

namespace Database\Factories;

use App\Models\Patient;
use App\Models\PatientAllergy;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PatientAllergy>
 */
class PatientAllergyFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'label' => fake()->randomElement(['Penicillin', 'Latex', 'Aspirin', 'Local anesthetic']),
            'is_critical' => fake()->boolean(20),
        ];
    }
}
