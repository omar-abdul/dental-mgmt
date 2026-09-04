<?php

namespace Database\Factories;

use App\Enums\ImagingOrderStatus;
use App\Enums\ImagingOrderType;
use App\Models\Dentist;
use App\Models\Encounter;
use App\Models\ImagingOrder;
use App\Models\Patient;
use App\Services\ImagingOrderNumberGenerator;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ImagingOrder>
 */
class ImagingOrderFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'number' => app(ImagingOrderNumberGenerator::class)->generate(),
            'patient_id' => Patient::factory(),
            'dentist_id' => Dentist::factory(),
            'encounter_id' => null,
            'type' => ImagingOrderType::Bitewing,
            'notes' => fake()->optional()->paragraph(),
            'status' => ImagingOrderStatus::Ordered,
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

    public function withStatus(ImagingOrderStatus $status): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => $status,
        ]);
    }

    public function withType(ImagingOrderType $type): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => $type,
        ]);
    }
}
