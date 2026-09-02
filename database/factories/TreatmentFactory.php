<?php

namespace Database\Factories;

use App\Enums\TreatmentStatus;
use App\Models\Appointment;
use App\Models\Dentist;
use App\Models\Patient;
use App\Models\Treatment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Treatment>
 */
class TreatmentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'dentist_id' => Dentist::factory(),
            'appointment_id' => null,
            'diagnosed_at' => fake()->dateTimeBetween('-30 days', 'now'),
            'diagnosis' => fake()->sentence(),
            'status' => TreatmentStatus::Planned,
            'notes' => fake()->optional()->paragraph(),
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

    public function forAppointment(?Appointment $appointment = null): static
    {
        return $this->state(function (array $attributes) use ($appointment): array {
            $appointment ??= Appointment::factory()->create();

            return [
                'appointment_id' => $appointment->id,
                'patient_id' => $appointment->patient_id,
                'dentist_id' => $appointment->dentist_id,
            ];
        });
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TreatmentStatus::Completed,
        ]);
    }
}
