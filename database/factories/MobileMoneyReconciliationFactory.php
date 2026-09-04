<?php

namespace Database\Factories;

use App\Enums\MobileMoneyProvider;
use App\Enums\ReconciliationStatus;
use App\Models\MobileMoneyReconciliation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MobileMoneyReconciliation>
 */
class MobileMoneyReconciliationFactory extends Factory
{
    protected $model = MobileMoneyReconciliation::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $systemTotal = fake()->numberBetween(10000, 100000);
        $providerTotal = $systemTotal + fake()->numberBetween(-500, 500);

        return [
            'reconciliation_date' => fake()->dateTimeBetween('-30 days', 'now'),
            'provider' => fake()->randomElement(MobileMoneyProvider::cases()),
            'transaction_count' => fake()->numberBetween(1, 20),
            'system_total_cents' => $systemTotal,
            'provider_total_cents' => $providerTotal,
            'difference_cents' => $providerTotal - $systemTotal,
            'reconciled_by' => User::factory(),
            'reconciled_at' => now(),
            'status' => ReconciliationStatus::Reconciled,
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
