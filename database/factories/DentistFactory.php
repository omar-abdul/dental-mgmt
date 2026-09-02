<?php

namespace Database\Factories;

use App\Enums\ClinicRole;
use App\Models\Chair;
use App\Models\Dentist;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Dentist>
 */
class DentistFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->dentist(),
            'display_name' => 'Dr. '.fake()->lastName(),
            'default_chair_id' => null,
            'is_active' => true,
        ];
    }

    public function withDefaultChair(?Chair $chair = null): static
    {
        return $this->state(fn (array $attributes) => [
            'default_chair_id' => ($chair ?? Chair::factory()->create())->id,
        ]);
    }

    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
            'display_name' => $user->name,
        ])->afterMaking(function (Dentist $dentist) use ($user): void {
            if ($user->role !== ClinicRole::Dentist) {
                $user->update(['role' => ClinicRole::Dentist]);
            }
        });
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
