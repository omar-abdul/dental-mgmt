<?php

use App\Enums\Gender;
use App\Models\AuditLog;
use App\Models\Patient;
use App\Models\User;

test('receptionist can register a patient from the form and see the record', function () {
    $receptionist = User::factory()->receptionist()->create();

    $this->actingAs($receptionist);

    $page = visit(route('patients.index'));

    $page->assertSee('No patients found.')
        ->click('@register-patient-link')
        ->assertSee('Register patient')
        ->fill('first_name', 'Maria')
        ->fill('last_name', 'Santos')
        ->fill('date_of_birth', '1990-05-15')
        ->select('gender', Gender::Female->value)
        ->fill('phone', '+252611234567')
        ->fill('email', 'maria@example.com')
        ->click('@save-patient-button')
        ->assertSee('Maria Santos')
        ->assertNoJavaScriptErrors();

    $patient = Patient::query()->first();

    expect($patient)->not->toBeNull()
        ->and($patient->first_name)->toBe('Maria')
        ->and($patient->last_name)->toBe('Santos')
        ->and($patient->phone)->toBe('+252611234567')
        ->and($patient->patient_number)->toMatch('/^PAT-\d{4}-\d{5}$/');
});

test('receptionist can search show and archive a patient', function () {
    $receptionist = User::factory()->receptionist()->create();
    $patient = Patient::factory()->create([
        'first_name' => 'Ahmed',
        'last_name' => 'Ali',
        'patient_number' => 'PAT-2026-00001',
        'phone' => '+252619998877',
    ]);

    $this->actingAs($receptionist);

    $page = visit(route('patients.index'));

    $page->fill('@patient-search-input', 'Ahmed')
        ->click('@patient-search-button')
        ->assertSee('PAT-2026-00001')
        ->assertSee('Ahmed Ali')
        ->click('PAT-2026-00001')
        ->assertSee('Ahmed Ali')
        ->assertSee('+252619998877')
        ->click('@archive-patient-button')
        ->assertSee('This patient is archived and read-only.')
        ->assertNoJavaScriptErrors();

    expect($patient->fresh()->trashed())->toBeTrue();
    expect(AuditLog::query()->where('action', 'patient.viewed')->count())->toBeGreaterThan(0);
});
