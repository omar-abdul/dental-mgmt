<?php

namespace Database\Factories;

use App\Models\AuditLog;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditLog>
 */
class AuditLogFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'action' => fake()->randomElement(['created', 'updated', 'viewed', 'archived']),
            'auditable_type' => Patient::class,
            'auditable_id' => Patient::factory(),
            'user_id' => User::factory(),
            'meta' => ['source' => 'factory'],
            'ip' => fake()->ipv4(),
        ];
    }
}
