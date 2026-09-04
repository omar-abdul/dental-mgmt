<?php

namespace Database\Factories;

use App\Models\SoapNote;
use App\Models\SoapNoteAmendment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SoapNoteAmendment>
 */
class SoapNoteAmendmentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'soap_note_id' => SoapNote::factory(),
            'body' => fake()->sentence(),
        ];
    }
}
