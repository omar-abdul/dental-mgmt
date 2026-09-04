<?php

use App\Enums\AppointmentStatus;
use App\Enums\TreatmentStatus;
use App\Models\Appointment;
use App\Models\Dentist;
use App\Models\FeeItem;
use App\Models\Patient;
use App\Models\PatientAllergy;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\Treatment;
use App\Models\TreatmentProcedure;
use App\Models\User;
use Illuminate\Support\Carbon;

beforeEach(function () {
    Carbon::setTestNow(Carbon::parse('2026-09-02 10:00:00', 'Africa/Mogadishu'));
});

afterEach(function () {
    Carbon::setTestNow();
});

function validTreatmentPayload(array $overrides = []): array
{
    $patient = Patient::factory()->create();
    $dentist = Dentist::factory()->create();
    $feeItem = FeeItem::factory()->create(['price_cents' => 5000]);

    return array_merge([
        'patient_id' => $patient->id,
        'dentist_id' => $dentist->id,
        'appointment_id' => null,
        'diagnosis' => 'Dental caries on tooth 36',
        'status' => TreatmentStatus::Planned->value,
        'notes' => null,
        'procedures' => [
            [
                'fee_item_id' => $feeItem->id,
                'quantity' => 2,
                'tooth_fdi' => '36',
            ],
        ],
        'prescription_items' => [
            [
                'medication' => 'Amoxicillin',
                'dosage' => '500mg',
                'instructions' => 'Take one capsule three times daily for 5 days.',
            ],
        ],
    ], $overrides);
}

test('accountant cannot view treatments index', function () {
    $user = User::factory()->accountant()->create();

    $this->actingAs($user)
        ->get(route('treatments.index'))
        ->assertForbidden();
});

test('lab cannot view treatments index', function () {
    $user = User::factory()->lab()->create();

    $this->actingAs($user)
        ->get(route('treatments.index'))
        ->assertForbidden();
});

test('dentist can view treatments index', function () {
    $user = User::factory()->dentist()->create();

    $this->actingAs($user)
        ->get(route('treatments.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('treatments/Index')
            ->has('treatments'));
});

test('nurse can view treatments index', function () {
    $user = User::factory()->nurse()->create();

    $this->actingAs($user)
        ->get(route('treatments.index'))
        ->assertOk();
});

test('receptionist can view treatments index', function () {
    $user = User::factory()->receptionist()->create();

    $this->actingAs($user)
        ->get(route('treatments.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('treatments/Index'));
});

test('guest is redirected to login when visiting treatments index', function () {
    $this->get(route('treatments.index'))
        ->assertRedirectToRoute('login');
});

test('dentist can create treatment with prescription and server-side fee cents', function () {
    $dentistUser = User::factory()->dentist()->create();
    $dentist = Dentist::factory()->forUser($dentistUser)->create();
    $feeItem = FeeItem::factory()->create(['price_cents' => 7500]);
    $patient = Patient::factory()->create();

    $payload = validTreatmentPayload([
        'patient_id' => $patient->id,
        'dentist_id' => $dentist->id,
        'procedures' => [
            [
                'fee_item_id' => $feeItem->id,
                'quantity' => 3,
                'tooth_fdi' => null,
            ],
        ],
    ]);

    $this->actingAs($dentistUser)
        ->post(route('treatments.store'), $payload)
        ->assertRedirect();

    $treatment = Treatment::query()->first();
    $prescription = Prescription::query()->first();
    $procedure = TreatmentProcedure::query()->first();

    expect($treatment)->not->toBeNull();
    expect($treatment->diagnosis)->toBe('Dental caries on tooth 36');
    expect($prescription)->not->toBeNull();
    expect($prescription->prescriber_id)->toBe($dentistUser->id);
    expect($prescription->number)->toMatch('/^RX-2026-\d{5}$/');
    expect($procedure->fee_cents)->toBe(22500);
    expect(PrescriptionItem::query()->count())->toBe(1);
});

test('admin can create a treatment', function () {
    $admin = User::factory()->admin()->create();
    $dentist = Dentist::factory()->create();

    $this->actingAs($admin)
        ->post(route('treatments.store'), validTreatmentPayload([
            'dentist_id' => $dentist->id,
        ]))
        ->assertRedirect();

    expect(Treatment::query()->count())->toBe(1);
    expect(Prescription::query()->first()?->prescriber_id)->toBe($admin->id);
});

test('nurse cannot create treatment or prescription', function () {
    $nurse = User::factory()->nurse()->create();

    $this->actingAs($nurse)
        ->post(route('treatments.store'), validTreatmentPayload())
        ->assertForbidden();

    expect(Treatment::query()->count())->toBe(0);
});

test('receptionist cannot create or update treatments', function () {
    $receptionist = User::factory()->receptionist()->create();
    $treatment = Treatment::factory()->create();

    $this->actingAs($receptionist)
        ->post(route('treatments.store'), validTreatmentPayload())
        ->assertForbidden();

    $this->actingAs($receptionist)
        ->post(route('treatments.complete', $treatment))
        ->assertForbidden();
});

test('patient show lists treatment history with diagnosis status and date', function () {
    $receptionist = User::factory()->receptionist()->create();
    $patient = Patient::factory()->create();
    $treatment = Treatment::factory()->forPatient($patient)->create([
        'diagnosis' => 'Gingivitis',
        'status' => TreatmentStatus::Completed,
        'diagnosed_at' => Carbon::parse('2026-08-15 14:30:00'),
    ]);

    $this->actingAs($receptionist)
        ->get(route('patients.show', $patient))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('patients/Show')
            ->where('patient.treatments.0.id', $treatment->id)
            ->where('patient.treatments.0.diagnosis', 'Gingivitis')
            ->where('patient.treatments.0.status', TreatmentStatus::Completed->value)
            ->where('patient.treatments.0.diagnosed_at', '2026-08-15'));
});

test('completing treatment sets linked appointment to completed', function () {
    $dentistUser = User::factory()->dentist()->create();
    $dentist = Dentist::factory()->forUser($dentistUser)->create();
    $patient = Patient::factory()->create();
    $appointment = Appointment::factory()->create([
        'patient_id' => $patient->id,
        'dentist_id' => $dentist->id,
        'status' => AppointmentStatus::CheckedIn,
    ]);

    $treatment = Treatment::factory()->create([
        'patient_id' => $patient->id,
        'dentist_id' => $dentist->id,
        'appointment_id' => $appointment->id,
        'status' => TreatmentStatus::InProgress,
    ]);

    $this->actingAs($dentistUser)
        ->post(route('treatments.complete', $treatment))
        ->assertRedirect(route('treatments.show', $treatment));

    $treatment->refresh();
    $appointment->refresh();

    expect($treatment->status)->toBe(TreatmentStatus::Completed);
    expect($appointment->status)->toBe(AppointmentStatus::Completed);
});

test('create form shows critical allergy alerts for selected patient', function () {
    $dentistUser = User::factory()->dentist()->create();
    Dentist::factory()->forUser($dentistUser)->create();
    $patient = Patient::factory()->create();

    PatientAllergy::factory()->create([
        'patient_id' => $patient->id,
        'label' => 'Penicillin',
        'is_critical' => true,
    ]);

    PatientAllergy::factory()->create([
        'patient_id' => $patient->id,
        'label' => 'Latex',
        'is_critical' => false,
    ]);

    $this->actingAs($dentistUser)
        ->get(route('treatments.create', ['patient_id' => $patient->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('treatments/Create')
            ->missing('patients')
            ->where('selectedPatient.id', $patient->id)
            ->where('criticalAlerts.allergies.0.label', 'Penicillin')
            ->where('criticalAlerts.allergies', fn ($allergies) => count($allergies) === 1));
});

test('treatment create no longer includes patient options list', function () {
    $dentistUser = User::factory()->dentist()->create();
    Dentist::factory()->forUser($dentistUser)->create();

    $this->actingAs($dentistUser)
        ->get(route('treatments.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('treatments/Create')
            ->missing('patients'));
});

test('fee cents ignores client-provided values and uses catalog price times quantity', function () {
    $admin = User::factory()->admin()->create();
    $dentist = Dentist::factory()->create();
    $feeItem = FeeItem::factory()->create(['price_cents' => 4200]);
    $patient = Patient::factory()->create();

    $payload = validTreatmentPayload([
        'patient_id' => $patient->id,
        'dentist_id' => $dentist->id,
        'procedures' => [
            [
                'fee_item_id' => $feeItem->id,
                'quantity' => 2,
                'fee_cents' => 1,
            ],
        ],
    ]);

    $this->actingAs($admin)
        ->post(route('treatments.store'), $payload)
        ->assertRedirect();

    expect(TreatmentProcedure::query()->first()?->fee_cents)->toBe(8400);
});

test('cancelled appointment cannot be linked to a treatment', function () {
    $dentistUser = User::factory()->dentist()->create();
    $dentist = Dentist::factory()->forUser($dentistUser)->create();
    $patient = Patient::factory()->create();
    $appointment = Appointment::factory()->cancelled()->create([
        'patient_id' => $patient->id,
        'dentist_id' => $dentist->id,
    ]);

    $this->actingAs($dentistUser)
        ->post(route('treatments.store'), validTreatmentPayload([
            'patient_id' => $patient->id,
            'dentist_id' => $dentist->id,
            'appointment_id' => $appointment->id,
        ]))
        ->assertSessionHasErrors('appointment_id');

    expect(Treatment::query()->count())->toBe(0);
});

test('storing treatment as completed sets linked appointment to completed', function () {
    $dentistUser = User::factory()->dentist()->create();
    $dentist = Dentist::factory()->forUser($dentistUser)->create();
    $patient = Patient::factory()->create();
    $appointment = Appointment::factory()->create([
        'patient_id' => $patient->id,
        'dentist_id' => $dentist->id,
        'status' => AppointmentStatus::CheckedIn,
    ]);

    $this->actingAs($dentistUser)
        ->post(route('treatments.store'), validTreatmentPayload([
            'patient_id' => $patient->id,
            'dentist_id' => $dentist->id,
            'appointment_id' => $appointment->id,
            'status' => TreatmentStatus::Completed->value,
        ]))
        ->assertRedirect();

    $appointment->refresh();

    expect(Treatment::query()->first()?->status)->toBe(TreatmentStatus::Completed);
    expect($appointment->status)->toBe(AppointmentStatus::Completed);
});

test('duplicate appointment_id on store returns validation error', function () {
    $dentistUser = User::factory()->dentist()->create();
    $dentist = Dentist::factory()->forUser($dentistUser)->create();
    $patient = Patient::factory()->create();
    $appointment = Appointment::factory()->create([
        'patient_id' => $patient->id,
        'dentist_id' => $dentist->id,
        'status' => AppointmentStatus::CheckedIn,
    ]);

    Treatment::factory()->create([
        'patient_id' => $patient->id,
        'dentist_id' => $dentist->id,
        'appointment_id' => $appointment->id,
    ]);

    $this->actingAs($dentistUser)
        ->post(route('treatments.store'), validTreatmentPayload([
            'patient_id' => $patient->id,
            'dentist_id' => $dentist->id,
            'appointment_id' => $appointment->id,
        ]))
        ->assertSessionHasErrors('appointment_id');

    expect(Treatment::query()->count())->toBe(1);
});

test('receptionist and nurse cannot access treatment create form', function () {
    $receptionist = User::factory()->receptionist()->create();
    $nurse = User::factory()->nurse()->create();

    $this->actingAs($receptionist)
        ->get(route('treatments.create'))
        ->assertForbidden();

    $this->actingAs($nurse)
        ->get(route('treatments.create'))
        ->assertForbidden();
});

test('archived patient cannot receive a new treatment', function () {
    $dentistUser = User::factory()->dentist()->create();
    $dentist = Dentist::factory()->forUser($dentistUser)->create();
    $archivedPatient = Patient::factory()->archived()->create();

    $this->actingAs($dentistUser)
        ->post(route('treatments.store'), validTreatmentPayload([
            'patient_id' => $archivedPatient->id,
            'dentist_id' => $dentist->id,
        ]))
        ->assertSessionHasErrors('patient_id');

    expect(Treatment::query()->count())->toBe(0);
});

test('inactive dentist cannot be assigned to a treatment', function () {
    $dentistUser = User::factory()->dentist()->create();
    $inactiveDentist = Dentist::factory()->inactive()->create();
    $patient = Patient::factory()->create();

    $this->actingAs($dentistUser)
        ->post(route('treatments.store'), validTreatmentPayload([
            'patient_id' => $patient->id,
            'dentist_id' => $inactiveDentist->id,
        ]))
        ->assertSessionHasErrors('dentist_id');

    expect(Treatment::query()->count())->toBe(0);
});

test('inactive fee item cannot be used in treatment procedures', function () {
    $dentistUser = User::factory()->dentist()->create();
    $dentist = Dentist::factory()->forUser($dentistUser)->create();
    $patient = Patient::factory()->create();
    $inactiveFeeItem = FeeItem::factory()->inactive()->create(['price_cents' => 5000]);

    $this->actingAs($dentistUser)
        ->post(route('treatments.store'), validTreatmentPayload([
            'patient_id' => $patient->id,
            'dentist_id' => $dentist->id,
            'procedures' => [
                [
                    'fee_item_id' => $inactiveFeeItem->id,
                    'quantity' => 1,
                    'tooth_fdi' => null,
                ],
            ],
        ]))
        ->assertSessionHasErrors('procedures.0.fee_item_id');

    expect(Treatment::query()->count())->toBe(0);
});

test('receptionist sees generate invoice on a completed treatment without an invoice', function () {
    $receptionist = User::factory()->receptionist()->create();
    $treatment = Treatment::factory()->completed()->create();

    $this->actingAs($receptionist)
        ->get(route('treatments.show', $treatment))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('treatments/Show')
            ->where('canGenerateInvoice', true)
            ->where('canComplete', false));
});

test('dentist cannot generate invoice from treatment show', function () {
    $dentistUser = User::factory()->dentist()->create();
    $treatment = Treatment::factory()->completed()->create();

    $this->actingAs($dentistUser)
        ->get(route('treatments.show', $treatment))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('treatments/Show')
            ->where('canGenerateInvoice', false));
});
