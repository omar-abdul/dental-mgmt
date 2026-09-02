<?php

test('database seeder creates the clinic admin', function () {
    $this->seed();

    $this->assertDatabaseHas('users', [
        'name' => 'Dr. A. Santos',
        'email' => 'a.santos@goldensmile.clinic',
        'role' => 'admin',
    ]);
});
