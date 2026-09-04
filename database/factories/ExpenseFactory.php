<?php

namespace Database\Factories;

use App\Models\Expense;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Expense>
 */
class ExpenseFactory extends Factory
{
    protected $model = Expense::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'description' => fake()->sentence(3),
            'category' => fake()->randomElement(['supplies', 'utilities', 'general']),
            'amount_cents' => fake()->numberBetween(500, 50000),
            'expense_date' => fake()->dateTimeBetween('-30 days', 'now'),
            'recorded_by' => User::factory(),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
