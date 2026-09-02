<?php

namespace Database\Factories;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Chair;
use App\Models\Dentist;
use App\Models\FeeItem;
use App\Models\Patient;
use Database\Factories\Concerns\GeneratesPublicNumbers;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Appointment>
 */
class AppointmentFactory extends Factory
{
    use GeneratesPublicNumbers;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startsAt = fake()->dateTimeBetween('+1 day', '+30 days');

        return [
            'number' => $this->dcmsPublicNumber('APT'),
            'patient_id' => Patient::factory(),
            'dentist_id' => Dentist::factory(),
            'chair_id' => Chair::factory(),
            'fee_item_id' => null,
            'starts_at' => $startsAt,
            'ends_at' => (clone $startsAt)->modify('+30 minutes'),
            'status' => AppointmentStatus::Scheduled,
            'reason' => fake()->optional()->sentence(),
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

    public function forChair(Chair $chair): static
    {
        return $this->for($chair);
    }

    public function withFeeItem(?FeeItem $feeItem = null): static
    {
        return $this->state(fn (array $attributes) => [
            'fee_item_id' => ($feeItem ?? FeeItem::factory()->create())->id,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AppointmentStatus::Cancelled,
        ]);
    }

    public function noShow(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AppointmentStatus::NoShow,
        ]);
    }
}
