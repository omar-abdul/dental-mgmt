<?php

namespace Database\Factories;

use App\Enums\ToothStatus;
use App\Models\OdontogramTooth;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OdontogramTooth>
 */
class OdontogramToothFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'tooth_fdi' => (string) fake()->randomElement(['11', '16', '21', '26', '31', '36', '41', '46']),
            'status' => ToothStatus::Healthy,
        ];
    }
}
