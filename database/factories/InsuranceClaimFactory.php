<?php

namespace Database\Factories;

use App\Enums\InsuranceClaimStatus;
use App\Models\InsuranceClaim;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InsuranceClaim>
 */
class InsuranceClaimFactory extends Factory
{
    protected $model = InsuranceClaim::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'invoice_id' => Invoice::factory(),
            'provider' => fake()->randomElement(['Sahal Insurance', 'Golis Health', 'SomHealth']),
            'status' => InsuranceClaimStatus::Draft,
            'created_by' => User::factory(),
        ];
    }
}
