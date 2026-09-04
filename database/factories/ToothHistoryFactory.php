<?php

namespace Database\Factories;

use App\Enums\ToothStatus;
use App\Models\Patient;
use App\Models\ToothHistory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ToothHistory>
 */
class ToothHistoryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'tooth_fdi' => '36',
            'previous_status' => ToothStatus::Healthy,
            'new_status' => ToothStatus::Caries,
            'surfaces' => ['M', 'O'],
            'notes' => fake()->optional()->sentence(),
            'encounter_id' => null,
        ];
    }
}
