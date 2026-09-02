<?php

namespace Database\Factories;

use App\Models\Chair;
use App\Models\Room;
use Database\Factories\Concerns\GeneratesPublicNumbers;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Chair>
 */
class ChairFactory extends Factory
{
    use GeneratesPublicNumbers;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'room_id' => Room::factory(),
            'name' => 'Chair '.fake()->numberBetween(1, 4),
            'code' => $this->chairCode(),
            'is_active' => true,
        ];
    }

    public function forRoom(Room $room): static
    {
        return $this->for($room);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
