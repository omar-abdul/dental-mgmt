<?php

namespace Database\Factories;

use App\Models\Dentist;
use App\Models\Encounter;
use App\Models\Patient;
use App\Models\Treatment;
use App\Services\EncounterNumberGenerator;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Encounter>
 */
class EncounterFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'number' => app(EncounterNumberGenerator::class)->generate(),
            'patient_id' => Patient::factory(),
            'dentist_id' => Dentist::factory(),
            'appointment_id' => null,
            'treatment_id' => null,
            'visited_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ];
    }

    public function forPatient(Patient $patient): static
    {
        return $this->for($patient);
    }

    public function forDentist(Dentist $dentist): static
    {
        return $this->for($dentist);
    }

    public function forTreatment(Treatment $treatment): static
    {
        return $this->state(fn (array $attributes) => [
            'patient_id' => $treatment->patient_id,
            'dentist_id' => $treatment->dentist_id,
            'appointment_id' => $treatment->appointment_id,
            'treatment_id' => $treatment->id,
            'visited_at' => $treatment->diagnosed_at,
        ]);
    }
}
