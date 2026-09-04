<?php

namespace Database\Factories;

use App\Models\ImageFile;
use App\Models\ImagingOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ImageFile>
 */
class ImageFileFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'imaging_order_id' => ImagingOrder::factory(),
            'disk' => 'local',
            'path' => 'imaging/'.fake()->uuid().'/sample.jpg',
            'original_name' => 'sample.jpg',
            'mime_type' => 'image/jpeg',
            'size_bytes' => fake()->numberBetween(1024, 102400),
        ];
    }

    public function forOrder(ImagingOrder $order): static
    {
        return $this->for($order);
    }
}
