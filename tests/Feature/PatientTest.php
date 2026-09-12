<?php

use App\Enums\ClinicRole;
use App\Enums\Gender;
use App\Enums\PatientStatus;
use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Treatment;
use App\Models\User;

function validPatientPayload(array $overrides = []): array
{
    return array_merge([
        'first_name' => 'Maria',
        'last_name' => 'Santos',
        'date_of_birth' => '1990-05-15',
        'gender' => Gender::Female->value,
        'phone' => '+252611234567',
        'email' => 'maria@example.com',
    ], $overrides);
}

function browserShapedPatientPayload(array $overrides = []): array
{
    return array_merge(validPatientPayload(), [
        'allergies' => [
            ['label' => ''],
        ],
        'conditions' => [
            ['label' => ''],
        ],
        'medications' => [
            ['label' => ''],
        ],
        'emergency_contact' => [
            'name' => '',
            'relationship' => '',
            'phone' => '',
        ],
    ], $overrides);
}

test('receptionist can create a patient with sequential patient number', function () {
    $receptionist = User::factory()->receptionist()->create();

    $response = $this->actingAs($receptionist)->post(route('patients.store'), validPatientPayload());

    $response->assertRedirect();

    $patient = Patient::query()->first();

    expect($patient)->not->toBeNull();
    expect($patient->patient_number)->toMatch('/^PAT-\d{4}-\d{5}$/');
    expect($patient->created_by)->toBe($receptionist->id);
    expect($patient->updated_by)->toBe($receptionist->id);
});

test('admin can create a patient', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('patients.store'), validPatientPayload([
            'first_name' => 'Ahmed',
            'last_name' => 'Ali',
            'email' => 'ahmed@example.com',
        ]))
        ->assertRedirect();

    $this->assertDatabaseHas('patients', [
        'first_name' => 'Ahmed',
        'last_name' => 'Ali',
        'email' => 'ahmed@example.com',
    ]);
});

test('patient numbers are sequential within the same year', function () {
    $admin = User::factory()->admin()->create();
    $year = now()->format('Y');

    Patient::factory()->create([
        'patient_number' => "PAT-{$year}-00009",
    ]);

    $this->actingAs($admin)->post(route('patients.store'), validPatientPayload([
        'first_name' => 'Next',
        'last_name' => 'Patient',
        'email' => 'next@example.com',
    ]));

    expect(Patient::query()->where('email', 'next@example.com')->value('patient_number'))
        ->toBe("PAT-{$year}-00010");
});

test('index search matches name patient number phone and email', function () {
    $receptionist = User::factory()->receptionist()->create();

    $patient = Patient::factory()->create([
        'first_name' => 'Searchable',
        'last_name' => 'Target',
        'patient_number' => 'PAT-2026-99999',
        'phone' => '+252619998877',
        'email' => 'searchable@example.com',
    ]);

    $this->actingAs($receptionist)
        ->get(route('patients.index', ['search' => 'Searchable']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('patients/Index')
            ->has('patients.data', 1)
            ->where('patients.data.0.id', $patient->id));

    $this->actingAs($receptionist)
        ->get(route('patients.index', ['search' => 'PAT-2026-99999']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('patients.data', 1));

    $this->actingAs($receptionist)
        ->get(route('patients.index', ['search' => '+252619998877']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('patients.data', 1));

    $this->actingAs($receptionist)
        ->get(route('patients.index', ['search' => 'searchable@example.com']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('patients.data', 1));
});

test('receptionist can update a non archived patient', function () {
    $receptionist = User::factory()->receptionist()->create();
    $patient = Patient::factory()->create([
        'first_name' => 'Before',
        'phone' => '+252610000001',
    ]);

    $this->actingAs($receptionist)
        ->patch(route('patients.update', $patient), validPatientPayload([
            'first_name' => 'After',
            'phone' => '+252610000002',
        ]))
        ->assertRedirect(route('patients.show', $patient));

    $patient->refresh();

    expect($patient->first_name)->toBe('After');
    expect($patient->phone)->toBe('+252610000002');
    expect($patient->updated_by)->toBe($receptionist->id);
});

test('receptionist can soft delete a patient and related records', function () {
    $receptionist = User::factory()->receptionist()->create();
    $patient = Patient::factory()->create();
    $appointment = Appointment::factory()->forPatient($patient)->create();
    $treatment = Treatment::factory()->forPatient($patient)->create();
    $invoice = Invoice::factory()->forPatient($patient)->create();
    $payment = Payment::factory()->forInvoice($invoice)->create(['patient_id' => $patient->id]);

    $this->actingAs($receptionist)
        ->delete(route('patients.destroy', $patient))
        ->assertRedirect(route('patients.index'));

    expect($patient->fresh()->trashed())->toBeTrue();
    expect(Appointment::withTrashed()->find($appointment->id)?->trashed())->toBeTrue();
    expect(Treatment::withTrashed()->find($treatment->id)?->trashed())->toBeTrue();
    expect(Invoice::withTrashed()->find($invoice->id)?->trashed())->toBeTrue();
    expect(Payment::withTrashed()->find($payment->id)?->trashed())->toBeTrue();
    expect(Invoice::withTrashed()->find($invoice->id))->not->toBeNull();
    expect(Payment::withTrashed()->find($payment->id))->not->toBeNull();

    $this->assertDatabaseHas('audit_logs', [
        'action' => 'patient.deleted',
        'auditable_type' => Patient::class,
        'auditable_id' => $patient->id,
        'user_id' => $receptionist->id,
    ]);
});

test('deleted patient show exposes canDelete false and update forbidden', function () {
    $receptionist = User::factory()->receptionist()->create();
    $patient = Patient::factory()->create();

    $this->actingAs($receptionist)
        ->delete(route('patients.destroy', $patient))
        ->assertRedirect(route('patients.index'));

    $this->actingAs($receptionist)
        ->get(route('patients.show', $patient))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('patients/Show')
            ->where('canUpdate', false)
            ->where('canDelete', false));

    $this->actingAs($receptionist)
        ->patch(route('patients.update', $patient), validPatientPayload())
        ->assertForbidden();
});

test('already deleted patient destroy returns forbidden', function () {
    $receptionist = User::factory()->receptionist()->create();
    $patient = Patient::factory()->archived()->create();

    $this->actingAs($receptionist)
        ->delete(route('patients.destroy', $patient))
        ->assertForbidden();
});

test('duplicate first last and date of birth returns 422 unless confirmed', function () {
    $receptionist = User::factory()->receptionist()->create();

    $existing = Patient::factory()->create([
        'first_name' => 'Maria',
        'last_name' => 'Santos',
        'date_of_birth' => '1990-05-15',
        'patient_number' => 'PAT-2026-00001',
    ]);

    $response = $this->actingAs($receptionist)
        ->from(route('patients.create'))
        ->post(route('patients.store'), validPatientPayload([
            'first_name' => ' maria ',
            'last_name' => 'SANTOS',
        ]));

    $response->assertSessionHasErrors('duplicate');
    expect(session('errors')->get('duplicate')[0])->toContain($existing->patient_number);

    expect(Patient::query()->count())->toBe(1);

    $this->actingAs($receptionist)
        ->post(route('patients.store'), validPatientPayload([
            'first_name' => ' maria ',
            'last_name' => 'SANTOS',
            'confirm_duplicate' => true,
            'email' => 'duplicate@example.com',
        ]))
        ->assertRedirect();

    expect(Patient::query()->where('email', 'duplicate@example.com')->exists())->toBeTrue();
    expect(Patient::query()->count())->toBe(2);
});

test('duplicate warning includes soft deleted patients', function () {
    $receptionist = User::factory()->receptionist()->create();

    Patient::factory()->archived()->create([
        'first_name' => 'Archived',
        'last_name' => 'Duplicate',
        'date_of_birth' => '1985-01-01',
        'patient_number' => 'PAT-2026-00088',
    ]);

    $this->actingAs($receptionist)
        ->from(route('patients.create'))
        ->post(route('patients.store'), validPatientPayload([
            'first_name' => 'archived',
            'last_name' => 'duplicate',
            'date_of_birth' => '1985-01-01',
        ]))
        ->assertSessionHasErrors('duplicate');
});

test('dentist and nurse cannot create or delete patients', function (ClinicRole $role) {
    $user = User::factory()->role($role)->create();
    $patient = Patient::factory()->create();

    $this->actingAs($user)
        ->post(route('patients.store'), validPatientPayload([
            'email' => "{$role->value}-create@example.com",
        ]))
        ->assertForbidden();

    $this->actingAs($user)
        ->delete(route('patients.destroy', $patient))
        ->assertForbidden();
})->with([
    'dentist' => ClinicRole::Dentist,
    'nurse' => ClinicRole::Nurse,
]);

test('accountant and lab cannot view patients index', function (ClinicRole $role) {
    $user = User::factory()->role($role)->create();

    $this->actingAs($user)
        ->get(route('patients.index'))
        ->assertForbidden();
})->with([
    'accountant' => ClinicRole::Accountant,
    'lab' => ClinicRole::Lab,
]);

test('showing a patient writes an audit log row', function () {
    $dentist = User::factory()->dentist()->create();
    $patient = Patient::factory()->create();

    expect(AuditLog::query()->count())->toBe(0);

    $this->actingAs($dentist)
        ->get(route('patients.show', $patient))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('patients/Show'));

    $this->assertDatabaseHas('audit_logs', [
        'action' => 'patient.viewed',
        'auditable_type' => Patient::class,
        'auditable_id' => $patient->id,
        'user_id' => $dentist->id,
    ]);
});

test('guest is redirected to login when visiting patients', function () {
    $this->get(route('patients.index'))
        ->assertRedirectToRoute('login');
});

test('create succeeds with blank optional medical fields from the browser form', function () {
    $receptionist = User::factory()->receptionist()->create();

    $this->actingAs($receptionist)
        ->post(route('patients.store'), browserShapedPatientPayload([
            'email' => 'blank-medical@example.com',
        ]))
        ->assertRedirect();

    $patient = Patient::query()->where('email', 'blank-medical@example.com')->first();

    expect($patient)->not->toBeNull();
    expect($patient->allergies)->toHaveCount(0);
    expect($patient->conditions)->toHaveCount(0);
    expect($patient->medications)->toHaveCount(0);
    expect($patient->emergencyContacts)->toHaveCount(0);
});

test('update preserves multiple allergies when form posts all rows', function () {
    $receptionist = User::factory()->receptionist()->create();
    $patient = Patient::factory()->create();

    $firstAllergy = $patient->allergies()->create(['label' => 'Penicillin']);
    $secondAllergy = $patient->allergies()->create(['label' => 'Latex']);

    $this->actingAs($receptionist)
        ->patch(route('patients.update', $patient), array_merge(validPatientPayload(), [
            'allergies' => [
                ['id' => $firstAllergy->id, 'label' => 'Penicillin'],
                ['id' => $secondAllergy->id, 'label' => 'Latex'],
                ['label' => ''],
            ],
            'conditions' => [
                ['label' => ''],
            ],
            'medications' => [
                ['label' => ''],
            ],
            'emergency_contact' => [
                'name' => '',
                'relationship' => '',
                'phone' => '',
            ],
        ]))
        ->assertRedirect(route('patients.show', $patient));

    $patient->refresh();

    expect($patient->allergies()->count())->toBe(2);
    expect($patient->allergies->pluck('label')->sort()->values()->all())->toBe(['Latex', 'Penicillin']);
});

test('duplicate identity on update returns 422 unless confirmed', function () {
    $receptionist = User::factory()->receptionist()->create();

    $existing = Patient::factory()->create([
        'first_name' => 'Maria',
        'last_name' => 'Santos',
        'date_of_birth' => '1990-05-15',
        'patient_number' => 'PAT-2026-00001',
    ]);

    $patient = Patient::factory()->create([
        'first_name' => 'Different',
        'last_name' => 'Person',
        'date_of_birth' => '1988-03-20',
    ]);

    $this->actingAs($receptionist)
        ->from(route('patients.edit', $patient))
        ->patch(route('patients.update', $patient), validPatientPayload([
            'first_name' => ' maria ',
            'last_name' => 'SANTOS',
            'date_of_birth' => '1990-05-15',
        ]))
        ->assertSessionHasErrors('duplicate');

    expect(session('errors')->get('duplicate')[0])->toContain($existing->patient_number);

    $this->actingAs($receptionist)
        ->patch(route('patients.update', $patient), validPatientPayload([
            'first_name' => ' maria ',
            'last_name' => 'SANTOS',
            'date_of_birth' => '1990-05-15',
            'confirm_duplicate' => true,
        ]))
        ->assertRedirect(route('patients.show', $patient));

    $patient->refresh();

    expect($patient->first_name)->toBe('maria');
    expect($patient->last_name)->toBe('SANTOS');
});

test('accountant and lab are forbidden from show create and update', function (ClinicRole $role) {
    $user = User::factory()->role($role)->create();
    $patient = Patient::factory()->create();

    $this->actingAs($user)
        ->get(route('patients.show', $patient))
        ->assertForbidden();

    $this->actingAs($user)
        ->get(route('patients.create'))
        ->assertForbidden();

    $this->actingAs($user)
        ->patch(route('patients.update', $patient), validPatientPayload())
        ->assertForbidden();
})->with([
    'accountant' => ClinicRole::Accountant,
    'lab' => ClinicRole::Lab,
]);

test('dentist can view patient but cannot update', function () {
    $dentist = User::factory()->dentist()->create();
    $patient = Patient::factory()->create();

    $this->actingAs($dentist)
        ->get(route('patients.show', $patient))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('patients/Show'));

    $this->actingAs($dentist)
        ->patch(route('patients.update', $patient), validPatientPayload([
            'first_name' => 'Blocked',
        ]))
        ->assertForbidden();
});

test('nurse cannot update patients', function () {
    $nurse = User::factory()->nurse()->create();
    $patient = Patient::factory()->create();

    $this->actingAs($nurse)
        ->patch(route('patients.update', $patient), validPatientPayload([
            'first_name' => 'Blocked',
        ]))
        ->assertForbidden();
});

test('index search omits soft deleted patients', function () {
    $receptionist = User::factory()->receptionist()->create();

    $deleted = Patient::factory()->archived()->create([
        'patient_number' => 'PAT-2026-77777',
        'first_name' => 'Deleted',
        'last_name' => 'Patient',
    ]);

    $this->actingAs($receptionist)
        ->get(route('patients.index', ['search' => 'PAT-2026-77777']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('patients/Index')
            ->has('patients.data', 0));

    expect($deleted->trashed())->toBeTrue();
});

test('patient search matches name patient number phone and email', function () {
    $receptionist = User::factory()->receptionist()->create();

    $patient = Patient::factory()->create([
        'first_name' => 'Searchable',
        'last_name' => 'Target',
        'patient_number' => 'PAT-2026-99999',
        'phone' => '+252619998877',
        'email' => 'searchable@example.com',
    ]);

    $this->actingAs($receptionist)
        ->getJson(route('patients.search', ['q' => 'Searchable']))
        ->assertOk()
        ->assertJsonCount(1, 'patients')
        ->assertJsonPath('patients.0.id', $patient->id);

    $this->actingAs($receptionist)
        ->getJson(route('patients.search', ['q' => 'PAT-2026-99999']))
        ->assertOk()
        ->assertJsonCount(1, 'patients');

    $this->actingAs($receptionist)
        ->getJson(route('patients.search', ['q' => '+252619998877']))
        ->assertOk()
        ->assertJsonCount(1, 'patients');

    $this->actingAs($receptionist)
        ->getJson(route('patients.search', ['q' => 'searchable@example.com']))
        ->assertOk()
        ->assertJsonCount(1, 'patients');
});

test('patient search returns empty for blank query', function () {
    $receptionist = User::factory()->receptionist()->create();

    Patient::factory()->create(['first_name' => 'Visible']);

    $this->actingAs($receptionist)
        ->getJson(route('patients.search', ['q' => '']))
        ->assertOk()
        ->assertExactJson(['patients' => []]);
});

test('patient search omits inactive patients', function () {
    $receptionist = User::factory()->receptionist()->create();

    $active = Patient::factory()->create([
        'first_name' => 'InactiveSearch',
        'last_name' => 'Active',
        'patient_number' => 'PAT-2026-55555',
    ]);

    Patient::factory()->create([
        'first_name' => 'InactiveSearch',
        'last_name' => 'Inactive',
        'patient_number' => 'PAT-2026-55556',
        'status' => PatientStatus::Inactive,
    ]);

    $this->actingAs($receptionist)
        ->getJson(route('patients.search', ['q' => 'InactiveSearch']))
        ->assertOk()
        ->assertJsonCount(1, 'patients')
        ->assertJsonPath('patients.0.id', $active->id);
});

test('patient search omits archived patients', function () {
    $receptionist = User::factory()->receptionist()->create();

    $active = Patient::factory()->create([
        'first_name' => 'Active',
        'last_name' => 'Picker',
        'patient_number' => 'PAT-2026-55555',
    ]);

    Patient::factory()->archived()->create([
        'first_name' => 'Active',
        'last_name' => 'Archived',
        'patient_number' => 'PAT-2026-55556',
    ]);

    $this->actingAs($receptionist)
        ->getJson(route('patients.search', ['q' => 'Active']))
        ->assertOk()
        ->assertJsonCount(1, 'patients')
        ->assertJsonPath('patients.0.id', $active->id);
});

test('accountant cannot search patients', function () {
    $accountant = User::factory()->accountant()->create();

    $this->actingAs($accountant)
        ->getJson(route('patients.search', ['q' => 'Maria']))
        ->assertForbidden();
});

test('lab can search patients for the order picker without patients module access', function () {
    $labUser = User::factory()->lab()->create();
    $patient = Patient::factory()->create([
        'first_name' => 'Maria',
        'last_name' => 'Santos',
        'patient_number' => 'PAT-2026-00042',
    ]);

    $this->actingAs($labUser)
        ->getJson(route('patients.search', ['q' => 'Maria']))
        ->assertOk()
        ->assertJsonCount(1, 'patients')
        ->assertJsonPath('patients.0.id', $patient->id);

    $this->actingAs($labUser)
        ->get(route('patients.index'))
        ->assertForbidden();

    $this->actingAs($labUser)
        ->get(route('patients.show', $patient))
        ->assertForbidden();
});

test('guest is redirected to login when searching patients', function () {
    $this->get(route('patients.search', ['q' => 'Maria']))
        ->assertRedirectToRoute('login');
});

test('update preserves is_critical on medical items when form omits the flag', function () {
    $receptionist = User::factory()->receptionist()->create();
    $patient = Patient::factory()->create();
    $allergy = $patient->allergies()->create(['label' => 'Penicillin', 'is_critical' => true]);

    $this->actingAs($receptionist)
        ->patch(route('patients.update', $patient), array_merge(validPatientPayload(), [
            'allergies' => [
                ['id' => $allergy->id, 'label' => 'Penicillin'],
            ],
            'conditions' => [
                ['label' => ''],
            ],
            'medications' => [
                ['label' => ''],
            ],
            'emergency_contact' => [
                'name' => '',
                'relationship' => '',
                'phone' => '',
            ],
        ]))
        ->assertRedirect(route('patients.show', $patient));

    expect($allergy->fresh()->is_critical)->toBeTrue();
});

test('deleted patient appointments are omitted from calendar index', function () {
    $receptionist = User::factory()->receptionist()->create();
    $patient = Patient::factory()->create(['first_name' => 'Calendar', 'last_name' => 'Hidden']);
    $appointment = Appointment::factory()->forPatient($patient)->create();

    $this->actingAs($receptionist)
        ->delete(route('patients.destroy', $patient))
        ->assertRedirect(route('patients.index'));

    $this->actingAs($receptionist)
        ->get(route('appointments.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('appointments/Index')
            ->where('appointments', fn ($appointments) => collect($appointments)
                ->pluck('id')
                ->doesntContain($appointment->id)));

    expect(Appointment::withTrashed()->find($appointment->id)?->trashed())->toBeTrue();
});

test('deleted patient invoices are omitted from billing index', function () {
    $receptionist = User::factory()->receptionist()->create();
    $patient = Patient::factory()->create();
    $invoice = Invoice::factory()->forPatient($patient)->create();

    $this->actingAs($receptionist)
        ->delete(route('patients.destroy', $patient))
        ->assertRedirect(route('patients.index'));

    $this->actingAs($receptionist)
        ->get(route('billing.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('billing/Index')
            ->where('invoices.data', fn ($invoices) => collect($invoices)
                ->pluck('id')
                ->doesntContain($invoice->id)));

    expect(Invoice::withTrashed()->find($invoice->id)?->trashed())->toBeTrue();
});

test('active patient show exposes canDelete for write roles', function () {
    $receptionist = User::factory()->receptionist()->create();
    $patient = Patient::factory()->create();

    $this->actingAs($receptionist)
        ->get(route('patients.show', $patient))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('patients/Show')
            ->where('canDelete', true));
});

test('guest is redirected when deleting a patient', function () {
    $patient = Patient::factory()->create();

    $this->delete(route('patients.destroy', $patient))
        ->assertRedirectToRoute('login');
});

test('dentist and nurse cannot delete patients', function (ClinicRole $role) {
    $user = User::factory()->role($role)->create();
    $patient = Patient::factory()->create();

    $this->actingAs($user)
        ->delete(route('patients.destroy', $patient))
        ->assertForbidden();

    expect(Patient::query()->find($patient->id))->not->toBeNull();
})->with([
    'dentist' => ClinicRole::Dentist,
    'nurse' => ClinicRole::Nurse,
]);
