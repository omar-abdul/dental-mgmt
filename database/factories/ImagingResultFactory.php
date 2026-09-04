<?php

namespace Database\Factories;

use App\Models\ImagingOrder;
use App\Models\ImagingResult;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ImagingResult>
 */
class ImagingResultFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'imaging_order_id' => ImagingOrder::factory(),
            'findings' => fake()->optional()->paragraph(),
            'impression' => fake()->optional()->sentence(),
            'reported_at' => now(),
        ];
    }

    public function forOrder(ImagingOrder $order): static
    {
        return $this->for($order);
    }
}
