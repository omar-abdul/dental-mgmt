<?php

use Database\Seeders\FeeItemSeeder;
use Database\Seeders\GoldenSmileNamedSeeder;
use Database\Seeders\WorkingHourSeeder;

test('golden smile named seeder creates the clinic admin', function () {
    $this->seed([
        FeeItemSeeder::class,
        WorkingHourSeeder::class,
        GoldenSmileNamedSeeder::class,
    ]);

    $this->assertDatabaseHas('users', [
        'name' => 'Dr. A. Santos',
        'email' => 'a.santos@goldensmile.clinic',
        'role' => 'admin',
    ]);
});
