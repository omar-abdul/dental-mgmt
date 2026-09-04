<?php

namespace Database\Factories;

use App\Enums\ToothSurface;
use App\Models\OdontogramSurface;
use App\Models\OdontogramTooth;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OdontogramSurface>
 */
class OdontogramSurfaceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'odontogram_tooth_id' => OdontogramTooth::factory(),
            'surface' => fake()->randomElement(ToothSurface::cases()),
        ];
    }
}
