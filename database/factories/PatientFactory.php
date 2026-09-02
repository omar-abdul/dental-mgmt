<?php

namespace Database\Factories;

use App\Enums\Gender;
use App\Enums\PatientStatus;
use App\Models\Patient;
use Database\Factories\Concerns\GeneratesPublicNumbers;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Patient>
 */
class PatientFactory extends Factory
{
    use GeneratesPublicNumbers;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'patient_number' => $this->dcmsPublicNumber('PAT'),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'date_of_birth' => fake()->date(),
            'gender' => fake()->randomElement(Gender::cases()),
            'phone' => fake()->numerify('+25261#######'),
            'email' => fake()->optional()->safeEmail(),
            'occupation' => fake()->optional()->jobTitle(),
            'address' => fake()->optional()->address(),
            'referred_by' => fake()->optional()->name(),
            'insurance_provider' => fake()->optional()->company(),
            'status' => PatientStatus::Active,
        ];
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PatientStatus::Archived,
        ])->afterCreating(function (Patient $patient): void {
            $patient->delete();
        });
    }
}
