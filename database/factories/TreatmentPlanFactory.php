<?php

namespace Database\Factories;

use App\Models\Dentist;
use App\Models\Patient;
use App\Models\TreatmentPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TreatmentPlan>
 */
class TreatmentPlanFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'dentist_id' => Dentist::factory(),
            'title' => fake()->optional()->words(3, true),
            'notes' => fake()->optional()->paragraph(),
        ];
    }
}
