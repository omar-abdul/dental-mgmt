<?php

use App\Enums\Gender;
use App\Models\Patient;
use App\Models\User;

test('login with a sql-like email stays on the login page', function () {
    User::factory()->receptionist()->create([
        'email' => 'frontdesk@goldensmile.clinic',
    ]);

    $page = visit(route('login'));

    $page->fill('email', "' OR '1'='1")
        ->fill('password', 'password12')
        ->click('@login-button')
        ->assertSee('Log in')
        ->assertNoJavaScriptErrors();

    $this->assertGuest();
});

test('registering a patient with xss in the name stores text and does not execute script', function () {
    $receptionist = User::factory()->receptionist()->create();
    $xss = '<script>alert(1)</script>';

    $this->actingAs($receptionist);

    $page = visit(route('patients.create'));

    $page->assertSee('Register patient')
        ->fill('first_name', $xss)
        ->fill('last_name', 'Safe')
        ->fill('date_of_birth', '1990-05-15')
        ->select('gender', Gender::Female->value)
        ->fill('phone', '+252611234567')
        ->click('@save-patient-button')
        ->assertSee('Safe')
        ->assertNoJavaScriptErrors();

    $patient = Patient::query()->first();

    expect($patient)->not->toBeNull()
        ->and($patient->first_name)->toBe($xss)
        ->and($patient->last_name)->toBe('Safe')
        ->and($patient->patient_number)->toMatch('/^PAT-\d{4}-\d{5}$/');
});

test('patient register with an invalid email stays on the form', function () {
    $receptionist = User::factory()->receptionist()->create();

    $this->actingAs($receptionist);

    $page = visit(route('patients.create'));

    $page->fill('first_name', 'Valid')
        ->fill('last_name', 'Person')
        ->fill('date_of_birth', '1990-05-15')
        ->select('gender', Gender::Female->value)
        ->fill('phone', '+252611234567')
        ->fill('email', 'not-an-email')
        ->click('@save-patient-button');

    expect(Patient::query()->count())->toBe(0);
    $page->assertSee('Register patient')->assertNoJavaScriptErrors();
});
