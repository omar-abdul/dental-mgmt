<?php

namespace Database\Seeders;

use App\Enums\ClinicRole;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Dr. A. Santos',
            'email' => 'a.santos@goldensmile.clinic',
            'password' => 'password12',
            'role' => ClinicRole::Admin,
        ]);
    }
}
