<?php

namespace Database\Factories;

use App\Models\Patient;
use App\Models\PatientCondition;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PatientCondition>
 */
class PatientConditionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'label' => fake()->randomElement(['Diabetes', 'Hypertension', 'Asthma', 'Heart disease']),
            'is_critical' => fake()->boolean(20),
        ];
    }
}
