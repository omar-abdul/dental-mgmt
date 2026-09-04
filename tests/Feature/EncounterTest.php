<?php

use App\Enums\ToothStatus;
use App\Enums\TreatmentPlanItemAcceptance;
use App\Enums\TreatmentStatus;
use App\Models\Dentist;
use App\Models\Encounter;
use App\Models\FeeItem;
use App\Models\Patient;
use App\Models\SoapNote;
use App\Models\SoapNoteAmendment;
use App\Models\Treatment;
use App\Models\TreatmentPlan;
use App\Models\TreatmentPlanItem;
use App\Models\User;
use Illuminate\Support\Carbon;

beforeEach(function () {
    Carbon::setTestNow(Carbon::parse('2026-09-04 10:00:00', 'Africa/Mogadishu'));
});

afterEach(function () {
    Carbon::setTestNow();
});

function encounterWithDraftSoap(array $overrides = []): Encounter
{
    $dentistUser = User::factory()->dentist()->create();
    $dentist = Dentist::factory()->forUser($dentistUser)->create();
    $patient = Patient::factory()->create();

    $encounter = Encounter::factory()->create(array_merge([
        'patient_id' => $patient->id,
        'dentist_id' => $dentist->id,
    ], $overrides));

    SoapNote::factory()->create([
        'encounter_id' => $encounter->id,
        'subjective' => 'Patient reports pain.',
    ]);

    return $encounter->fresh(['soapNote']);
}

test('storing treatment as completed creates an encounter', function () {
    $dentistUser = User::factory()->dentist()->create();
    $dentist = Dentist::factory()->forUser($dentistUser)->create();
    $patient = Patient::factory()->create();
    $feeItem = FeeItem::factory()->create(['price_cents' => 5000]);

    $this->actingAs($dentistUser)
        ->post(route('treatments.store'), [
            'patient_id' => $patient->id,
            'dentist_id' => $dentist->id,
            'appointment_id' => null,
            'diagnosis' => 'Completed visit',
            'status' => TreatmentStatus::Completed->value,
            'notes' => null,
            'procedures' => [
                ['fee_item_id' => $feeItem->id, 'quantity' => 1, 'tooth_fdi' => '36'],
            ],
            'prescription_items' => [
                ['medication' => 'Amoxicillin', 'dosage' => '500mg', 'instructions' => 'Take daily.'],
            ],
        ])
        ->assertRedirect();

    $treatment = Treatment::query()->first();

    expect($treatment?->encounter)->not->toBeNull()
        ->and($treatment->encounter->number)->toMatch('/^ENC-\d{4}-\d{5}$/');
});

test('completing a treatment creates an encounter with soap note', function () {
    $dentistUser = User::factory()->dentist()->create();
    $dentist = Dentist::factory()->forUser($dentistUser)->create();
    $treatment = Treatment::factory()->create([
        'dentist_id' => $dentist->id,
        'status' => TreatmentStatus::Planned,
    ]);

    $this->actingAs($dentistUser)
        ->post(route('treatments.complete', $treatment))
        ->assertRedirect(route('treatments.show', $treatment));

    $treatment->refresh();

    expect($treatment->status)->toBe(TreatmentStatus::Completed)
        ->and($treatment->encounter)->not->toBeNull()
        ->and($treatment->encounter->number)->toMatch('/^ENC-\d{4}-\d{5}$/')
        ->and($treatment->encounter->soapNote)->not->toBeNull();
});

test('dentist can draft and sign soap note', function () {
    $dentistUser = User::factory()->dentist()->create();
    $encounter = encounterWithDraftSoap();

    $this->actingAs($dentistUser)
        ->patch(route('encounters.soap.update', $encounter), [
            'subjective' => 'Updated subjective note.',
            'objective' => 'Visible caries on 36.',
            'assessment' => 'Dental caries.',
            'plan' => 'Restoration.',
        ])
        ->assertRedirect(route('encounters.show', $encounter));

    $soapNote = $encounter->fresh()->soapNote;

    expect($soapNote->subjective)->toBe('Updated subjective note.')
        ->and($soapNote->isSigned())->toBeFalse();

    $this->actingAs($dentistUser)
        ->post(route('encounters.sign', $encounter))
        ->assertRedirect(route('encounters.show', $encounter));

    $soapNote->refresh();

    expect($soapNote->isSigned())->toBeTrue()
        ->and($soapNote->signed_by)->toBe($dentistUser->id);
});

test('signed soap note cannot be silently edited', function () {
    $dentistUser = User::factory()->dentist()->create();
    $encounter = encounterWithDraftSoap();

    $encounter->soapNote->update([
        'subjective' => 'Initial note.',
    ]);
    $encounter->soapNote->sign($dentistUser);

    $this->actingAs($dentistUser)
        ->patch(route('encounters.soap.update', $encounter), [
            'subjective' => 'Silent edit attempt.',
        ])
        ->assertForbidden();
});

test('amendment is required to add text after sign', function () {
    $dentistUser = User::factory()->dentist()->create();
    $encounter = encounterWithDraftSoap();

    $encounter->soapNote->update([
        'subjective' => 'Signed content.',
    ]);
    $encounter->soapNote->sign($dentistUser);

    $this->actingAs($dentistUser)
        ->post(route('encounters.amendments.store', $encounter), [
            'body' => 'Correction: note tooth 37 instead of 36.',
        ])
        ->assertRedirect(route('encounters.show', $encounter));

    expect(SoapNoteAmendment::query()->count())->toBe(1)
        ->and(SoapNoteAmendment::query()->first()->body)
        ->toBe('Correction: note tooth 37 instead of 36.');
});

test('receptionist cannot write to chart or encounters', function () {
    $receptionist = User::factory()->receptionist()->create();
    $encounter = encounterWithDraftSoap();
    $patient = $encounter->patient;

    $this->actingAs($receptionist)
        ->patch(route('encounters.soap.update', $encounter), [
            'subjective' => 'Blocked edit.',
        ])
        ->assertForbidden();

    $this->actingAs($receptionist)
        ->post(route('encounters.sign', $encounter))
        ->assertForbidden();

    $this->actingAs($receptionist)
        ->patch(route('patients.odontogram.update', $patient), [
            'tooth_fdi' => '36',
            'status' => ToothStatus::Caries->value,
        ])
        ->assertForbidden();

    $this->actingAs($receptionist)
        ->post(route('patients.chart.plans.store', $patient), [
            'dentist_id' => $encounter->dentist_id,
            'title' => 'Blocked plan',
        ])
        ->assertForbidden();
});

test('nurse can view encounter but not write', function () {
    $nurse = User::factory()->nurse()->create();
    $encounter = encounterWithDraftSoap();

    $this->actingAs($nurse)
        ->get(route('encounters.show', $encounter))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('encounters/Show')
            ->where('canUpdateSoap', false)
            ->where('canSign', false));

    $this->actingAs($nurse)
        ->patch(route('encounters.soap.update', $encounter), [
            'subjective' => 'Blocked edit.',
        ])
        ->assertForbidden();
});

test('odontogram update persists tooth history', function () {
    $dentistUser = User::factory()->dentist()->create();
    $patient = Patient::factory()->create();

    $this->actingAs($dentistUser)
        ->patch(route('patients.odontogram.update', $patient), [
            'tooth_fdi' => '36',
            'status' => ToothStatus::Caries->value,
            'surfaces' => ['M', 'O'],
            'notes' => 'Initial caries noted.',
        ])
        ->assertRedirect(route('patients.chart', $patient));

    $patient->refresh();

    expect($patient->odontogramTeeth)->toHaveCount(1)
        ->and($patient->odontogramTeeth->first()->status)->toBe(ToothStatus::Caries)
        ->and($patient->odontogramTeeth->first()->surfaces)->toHaveCount(2)
        ->and($patient->toothHistories)->toHaveCount(1);
});

test('receptionist cannot access chart index', function () {
    $receptionist = User::factory()->receptionist()->create();

    $this->actingAs($receptionist)
        ->get(route('chart.index'))
        ->assertForbidden();
});

test('accountant cannot access chart index', function () {
    $accountant = User::factory()->accountant()->create();

    $this->actingAs($accountant)
        ->get(route('chart.index'))
        ->assertForbidden();
});

test('receptionist cannot view patient chart or encounter', function () {
    $receptionist = User::factory()->receptionist()->create();
    $encounter = encounterWithDraftSoap();
    $patient = $encounter->patient;

    $this->actingAs($receptionist)
        ->get(route('patients.chart', $patient))
        ->assertForbidden();

    $this->actingAs($receptionist)
        ->get(route('encounters.show', $encounter))
        ->assertForbidden();
});

test('nurse can view patient chart read-only', function () {
    $nurse = User::factory()->nurse()->create();
    $patient = Patient::factory()->create();

    $this->actingAs($nurse)
        ->get(route('patients.chart', $patient))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('chart/PatientChart')
            ->where('canUpdateOdontogram', false)
            ->where('canCreatePlan', false));
});

test('patch body cannot forge soap signature', function () {
    $dentistUser = User::factory()->dentist()->create();
    $encounter = encounterWithDraftSoap();

    $this->actingAs($dentistUser)
        ->patch(route('encounters.soap.update', $encounter), [
            'subjective' => 'Updated subjective note.',
            'signed_at' => now()->toIso8601String(),
            'signed_by' => $dentistUser->id,
        ])
        ->assertRedirect(route('encounters.show', $encounter));

    $soapNote = $encounter->fresh()->soapNote;

    expect($soapNote->subjective)->toBe('Updated subjective note.')
        ->and($soapNote->isSigned())->toBeFalse()
        ->and($soapNote->signed_by)->toBeNull();
});

test('dentist can create treatment plan item and update acceptance', function () {
    $dentistUser = User::factory()->dentist()->create();
    $dentist = Dentist::factory()->forUser($dentistUser)->create();
    $patient = Patient::factory()->create();

    $this->actingAs($dentistUser)
        ->post(route('patients.chart.plans.store', $patient), [
            'dentist_id' => $dentist->id,
            'title' => 'Restorative plan',
        ])
        ->assertRedirect(route('patients.chart', $patient));

    $plan = TreatmentPlan::query()->firstOrFail();

    $this->actingAs($dentistUser)
        ->post(route('treatment-plans.items.store', $plan), [
            'description' => 'Composite on 36',
            'tooth_fdi' => '36',
            'fee_cents' => 15000,
            'acceptance_status' => TreatmentPlanItemAcceptance::Proposed->value,
        ])
        ->assertRedirect(route('patients.chart', $patient));

    $item = TreatmentPlanItem::query()->firstOrFail();

    expect($item->acceptance_status)->toBe(TreatmentPlanItemAcceptance::Proposed);

    $this->actingAs($dentistUser)
        ->patch(route('treatment-plans.items.update', [$plan, $item]), [
            'acceptance_status' => TreatmentPlanItemAcceptance::Accepted->value,
        ])
        ->assertRedirect(route('patients.chart', $patient));

    expect($item->fresh()->acceptance_status)->toBe(TreatmentPlanItemAcceptance::Accepted);
});

test('odontogram update links tooth history to provided encounter', function () {
    $dentistUser = User::factory()->dentist()->create();
    $dentist = Dentist::factory()->forUser($dentistUser)->create();
    $patient = Patient::factory()->create();

    $encounter = Encounter::factory()->create([
        'patient_id' => $patient->id,
        'dentist_id' => $dentist->id,
    ]);

    SoapNote::factory()->create([
        'encounter_id' => $encounter->id,
    ]);

    $this->actingAs($dentistUser)
        ->patch(route('patients.odontogram.update', $patient), [
            'tooth_fdi' => '36',
            'status' => ToothStatus::Caries->value,
            'encounter_id' => $encounter->id,
        ])
        ->assertRedirect(route('patients.chart', $patient));

    expect($patient->fresh()->toothHistories->first()?->encounter_id)->toBe($encounter->id);
});

test('odontogram update auto-links latest unsigned encounter', function () {
    $dentistUser = User::factory()->dentist()->create();
    $dentist = Dentist::factory()->forUser($dentistUser)->create();
    $patient = Patient::factory()->create();

    $signedEncounter = Encounter::factory()->create([
        'patient_id' => $patient->id,
        'dentist_id' => $dentist->id,
        'visited_at' => now()->subDay(),
    ]);

    SoapNote::factory()->create([
        'encounter_id' => $signedEncounter->id,
    ])->sign($dentistUser);

    $openEncounter = Encounter::factory()->create([
        'patient_id' => $patient->id,
        'dentist_id' => $dentist->id,
        'visited_at' => now(),
    ]);

    SoapNote::factory()->create([
        'encounter_id' => $openEncounter->id,
    ]);

    $this->actingAs($dentistUser)
        ->patch(route('patients.odontogram.update', $patient), [
            'tooth_fdi' => '36',
            'status' => ToothStatus::Caries->value,
        ])
        ->assertRedirect(route('patients.chart', $patient));

    expect($patient->fresh()->toothHistories->first()?->encounter_id)->toBe($openEncounter->id);
});
