<?php

use App\Models\Dentist;
use App\Models\Encounter;
use App\Models\Patient;
use App\Models\SoapNote;
use App\Models\SoapNoteAmendment;
use App\Models\User;

test('dentist signs encounter and cannot silently edit then submits amendment', function () {
    $dentistUser = User::factory()->dentist()->create(['name' => 'Dr. R. Lim']);
    $dentist = Dentist::factory()->forUser($dentistUser)->create();
    $patient = Patient::factory()->create([
        'first_name' => 'Ahmed',
        'last_name' => 'Ali',
        'patient_number' => 'PAT-2026-00099',
    ]);

    $encounter = Encounter::factory()->create([
        'patient_id' => $patient->id,
        'dentist_id' => $dentist->id,
        'number' => 'ENC-2026-00099',
    ]);

    SoapNote::factory()->create([
        'encounter_id' => $encounter->id,
        'subjective' => null,
        'objective' => null,
        'assessment' => null,
        'plan' => null,
    ]);

    $this->actingAs($dentistUser);

    $page = visit(route('encounters.show', $encounter));

    $page->assertSee('ENC-2026-00099')
        ->assertSee('Draft')
        ->fill('@soap-subjective', 'Patient reports sensitivity on lower left.')
        ->fill('@soap-objective', 'Caries visible on tooth 36.')
        ->fill('@soap-assessment', 'Dental caries.')
        ->fill('@soap-plan', 'Composite restoration planned.')
        ->click('@save-soap-button')
        ->click('@sign-encounter-button')
        ->assertSee('Signed')
        ->assertNoJavaScriptErrors();

    $soapNote = $encounter->fresh()->soapNote;

    expect($soapNote)->not->toBeNull()
        ->and($soapNote->isSigned())->toBeTrue()
        ->and($soapNote->subjective)->toBe('Patient reports sensitivity on lower left.');

    $this->actingAs($dentistUser)
        ->patch(route('encounters.soap.update', $encounter), [
            'subjective' => 'Silent edit attempt.',
        ])
        ->assertForbidden();

    $page = visit(route('encounters.show', $encounter));

    $page->assertSee('Signed')
        ->fill('@amendment-body', 'Amendment: monitor pulp vitality at next visit.')
        ->click('@submit-amendment-button')
        ->assertSee('Amendment: monitor pulp vitality at next visit.')
        ->assertNoJavaScriptErrors();

    expect(SoapNoteAmendment::query()->count())->toBe(1);
});

test('receptionist sidebar hides chart module', function () {
    $receptionist = User::factory()->receptionist()->create();

    $this->actingAs($receptionist);

    visit(route('dashboard'))
        ->assertSee('Dashboard')
        ->assertDontSee('Chart')
        ->assertNoJavaScriptErrors();
});

test('receptionist cannot write clinical chart data', function () {
    $receptionist = User::factory()->receptionist()->create();
    $dentistUser = User::factory()->dentist()->create();
    $dentist = Dentist::factory()->forUser($dentistUser)->create();
    $patient = Patient::factory()->create([
        'first_name' => 'Fatima',
        'last_name' => 'Hassan',
        'patient_number' => 'PAT-2026-00101',
    ]);

    $encounter = Encounter::factory()->create([
        'patient_id' => $patient->id,
        'dentist_id' => $dentist->id,
        'number' => 'ENC-2026-00101',
    ]);

    SoapNote::factory()->create([
        'encounter_id' => $encounter->id,
        'subjective' => 'Initial subjective note.',
    ]);

    $this->actingAs($receptionist);

    visit(route('encounters.show', $encounter))
        ->assertDontSee('Save draft')
        ->assertDontSee('Sign encounter')
        ->assertNoJavaScriptErrors();

    visit(route('patients.chart', $patient))
        ->assertDontSee('Save tooth')
        ->assertDontSee('Create treatment plan')
        ->assertNoJavaScriptErrors();

    $originalSubjective = $encounter->fresh()->soapNote->subjective;

    $this->actingAs($receptionist)
        ->patch(route('encounters.soap.update', $encounter), [
            'subjective' => 'Receptionist edit attempt.',
        ])
        ->assertForbidden();

    $this->actingAs($receptionist)
        ->post(route('encounters.sign', $encounter))
        ->assertForbidden();

    $this->actingAs($receptionist)
        ->patch(route('patients.odontogram.update', $patient), [
            'tooth_fdi' => '36',
            'status' => 'caries',
        ])
        ->assertForbidden();

    expect($encounter->fresh()->soapNote->subjective)->toBe($originalSubjective)
        ->and($encounter->fresh()->soapNote->isSigned())->toBeFalse()
        ->and($patient->fresh()->odontogramTeeth)->toHaveCount(0);
});
