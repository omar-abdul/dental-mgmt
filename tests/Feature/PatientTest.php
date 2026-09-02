<?php

use App\Enums\ClinicRole;
use App\Enums\Gender;
use App\Enums\PatientStatus;
use App\Models\AuditLog;
use App\Models\Patient;
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

test('receptionist can archive a patient and archived patients are read only', function () {
    $receptionist = User::factory()->receptionist()->create();
    $patient = Patient::factory()->create();

    $this->actingAs($receptionist)
        ->post(route('patients.archive', $patient))
        ->assertRedirect(route('patients.show', $patient));

    $patient->refresh();

    expect($patient->status)->toBe(PatientStatus::Archived);
    expect($patient->trashed())->toBeTrue();

    $this->actingAs($receptionist)
        ->get(route('patients.show', $patient))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('patients/Show')
            ->where('patient.is_archived', true)
            ->where('canUpdate', false)
            ->where('canArchive', false));

    $this->actingAs($receptionist)
        ->get(route('patients.edit', $patient))
        ->assertForbidden();

    $this->actingAs($receptionist)
        ->patch(route('patients.update', $patient), validPatientPayload())
        ->assertForbidden();

    $this->actingAs($receptionist)
        ->post(route('patients.archive', $patient))
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

test('dentist and nurse cannot create or archive patients', function (ClinicRole $role) {
    $user = User::factory()->role($role)->create();
    $patient = Patient::factory()->create();

    $this->actingAs($user)
        ->post(route('patients.store'), validPatientPayload([
            'email' => "{$role->value}-create@example.com",
        ]))
        ->assertForbidden();

    $this->actingAs($user)
        ->post(route('patients.archive', $patient))
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

test('index search finds archived patients by patient number', function () {
    $receptionist = User::factory()->receptionist()->create();

    $archived = Patient::factory()->archived()->create([
        'patient_number' => 'PAT-2026-77777',
        'first_name' => 'Archived',
        'last_name' => 'Patient',
    ]);

    $this->actingAs($receptionist)
        ->get(route('patients.index', ['search' => 'PAT-2026-77777']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('patients/Index')
            ->has('patients.data', 1)
            ->where('patients.data.0.id', $archived->id)
            ->where('patients.data.0.is_archived', true));
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

test('accountant and lab cannot search patients', function (ClinicRole $role) {
    $user = User::factory()->role($role)->create();

    $this->actingAs($user)
        ->getJson(route('patients.search', ['q' => 'Maria']))
        ->assertForbidden();
})->with([
    'accountant' => ClinicRole::Accountant,
    'lab' => ClinicRole::Lab,
]);

test('guest is redirected to login when searching patients', function () {
    $this->get(route('patients.search', ['q' => 'Maria']))
        ->assertRedirectToRoute('login');
});
