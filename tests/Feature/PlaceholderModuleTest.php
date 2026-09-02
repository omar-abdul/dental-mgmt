<?php

use App\Models\User;

test('accountant cannot view patients module', function () {
    $user = User::factory()->accountant()->create();

    $this->actingAs($user)
        ->get(route('patients.index'))
        ->assertForbidden();
});

test('dentist can view patients module', function () {
    $user = User::factory()->dentist()->create();

    $this->actingAs($user)
        ->get(route('patients.index'))
        ->assertOk();
});

test('lab cannot view billing module', function () {
    $user = User::factory()->lab()->create();

    $this->actingAs($user)
        ->get(route('billing.index'))
        ->assertForbidden();
});

test('accountant can view billing module', function () {
    $user = User::factory()->accountant()->create();

    $this->actingAs($user)
        ->get(route('billing.index'))
        ->assertOk();
});

test('accountant cannot view appointments module', function () {
    $user = User::factory()->accountant()->create();

    $this->actingAs($user)
        ->get(route('appointments.index'))
        ->assertForbidden();
});

test('receptionist can view treatments module', function () {
    $user = User::factory()->receptionist()->create();

    $this->actingAs($user)
        ->get(route('treatments.index'))
        ->assertOk();
});

test('guest is redirected to login when visiting patients module', function () {
    $this->get(route('patients.index'))
        ->assertRedirectToRoute('login');
});
