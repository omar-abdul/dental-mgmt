<?php

use App\Enums\ClinicRole;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $this->get(route('dashboard'))->assertRedirectToRoute('login');
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk();
});

test('each clinic role can visit the dashboard', function (ClinicRole $role) {
    $user = User::factory()->role($role)->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk();
})->with([
    'admin' => ClinicRole::Admin,
    'dentist' => ClinicRole::Dentist,
    'receptionist' => ClinicRole::Receptionist,
    'nurse' => ClinicRole::Nurse,
    'accountant' => ClinicRole::Accountant,
    'lab' => ClinicRole::Lab,
]);
