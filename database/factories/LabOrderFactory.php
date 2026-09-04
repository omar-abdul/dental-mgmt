<?php

namespace Database\Factories;

use App\Enums\LabOrderStatus;
use App\Models\Dentist;
use App\Models\Encounter;
use App\Models\LabOrder;
use App\Models\Patient;
use App\Models\Treatment;
use App\Services\LabOrderNumberGenerator;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LabOrder>
 */
class LabOrderFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'number' => app(LabOrderNumberGenerator::class)->generate(),
            'patient_id' => Patient::factory(),
            'dentist_id' => Dentist::factory(),
            'treatment_id' => null,
            'encounter_id' => null,
            'description' => fake()->sentence(),
            'notes' => fake()->optional()->paragraph(),
            'due_date' => fake()->optional()->dateTimeBetween('now', '+14 days'),
            'status' => LabOrderStatus::Ordered,
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

    public function forTreatment(?Treatment $treatment = null): static
    {
        return $this->state(function (array $attributes) use ($treatment): array {
            $treatment ??= Treatment::factory()->create();

            return [
                'treatment_id' => $treatment->id,
                'patient_id' => $treatment->patient_id,
                'dentist_id' => $treatment->dentist_id,
            ];
        });
    }

    public function forEncounter(?Encounter $encounter = null): static
    {
        return $this->state(function (array $attributes) use ($encounter): array {
            $encounter ??= Encounter::factory()->create();

            return [
                'encounter_id' => $encounter->id,
                'patient_id' => $encounter->patient_id,
                'dentist_id' => $encounter->dentist_id,
            ];
        });
    }

    public function withStatus(LabOrderStatus $status): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => $status,
        ]);
    }
}
