<?php

namespace Database\Factories;

use App\Models\DailyCashClosing;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DailyCashClosing>
 */
class DailyCashClosingFactory extends Factory
{
    protected $model = DailyCashClosing::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $systemTotal = fake()->numberBetween(10000, 100000);
        $countedTotal = $systemTotal + fake()->numberBetween(-500, 500);

        return [
            'closing_date' => fake()->dateTimeBetween('-30 days', 'now'),
            'system_cash_total_cents' => $systemTotal,
            'counted_cash_cents' => $countedTotal,
            'difference_cents' => $countedTotal - $systemTotal,
            'closed_by' => User::factory(),
            'closed_at' => now(),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
