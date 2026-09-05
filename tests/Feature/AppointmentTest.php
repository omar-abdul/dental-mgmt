<?php

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\AppointmentRevision;
use App\Models\Chair;
use App\Models\Dentist;
use App\Models\FeeItem;
use App\Models\Patient;
use App\Models\User;
use Database\Seeders\WorkingHourSeeder;
use Illuminate\Support\Carbon;

beforeEach(function () {
    Carbon::setTestNow(Carbon::parse('2026-09-02 09:00:00', 'Africa/Mogadishu'));

    $this->seed(WorkingHourSeeder::class);
});

afterEach(function () {
    Carbon::setTestNow();
});

function validAppointmentPayload(array $overrides = []): array
{
    $patient = Patient::factory()->create();
    $dentist = Dentist::factory()->withDefaultChair()->create();
    $chair = Chair::factory()->create();

    return array_merge([
        'patient_id' => $patient->id,
        'dentist_id' => $dentist->id,
        'chair_id' => $chair->id,
        'fee_item_id' => null,
        'starts_at' => '2026-09-02T09:00',
        'duration_minutes' => 30,
        'reason' => 'Routine checkup',
    ], $overrides);
}

test('accountant cannot view appointments index', function () {
    $user = User::factory()->accountant()->create();

    $this->actingAs($user)
        ->get(route('appointments.index'))
        ->assertForbidden();
});

test('lab cannot view appointments index', function () {
    $user = User::factory()->lab()->create();

    $this->actingAs($user)
        ->get(route('appointments.index'))
        ->assertForbidden();
});

test('dentist can view appointments index', function () {
    $user = User::factory()->dentist()->create();

    $this->actingAs($user)
        ->get(route('appointments.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('appointments/Index')
            ->has('columns')
            ->has('workingHours')
            ->has('appointments'));
});

test('guest is redirected to login when visiting appointments index', function () {
    $this->get(route('appointments.index'))
        ->assertRedirectToRoute('login');
});

test('receptionist can book an appointment with sequential number', function () {
    $receptionist = User::factory()->receptionist()->create();

    $this->actingAs($receptionist)
        ->post(route('appointments.store'), validAppointmentPayload())
        ->assertRedirect(route('appointments.index', ['date' => '2026-09-02']));

    $appointment = Appointment::query()->first();

    expect($appointment)->not->toBeNull();
    expect($appointment->number)->toBe('APT-2026-00001');
    expect($appointment->status)->toBe(AppointmentStatus::Scheduled);
});

test('dentist overlap returns 422', function () {
    $receptionist = User::factory()->receptionist()->create();
    $dentist = Dentist::factory()->withDefaultChair()->create();
    $chairA = Chair::factory()->create();
    $chairB = Chair::factory()->create();
    $patientA = Patient::factory()->create();
    $patientB = Patient::factory()->create();

    Appointment::factory()->create([
        'patient_id' => $patientA->id,
        'dentist_id' => $dentist->id,
        'chair_id' => $chairA->id,
        'starts_at' => Carbon::parse('2026-09-02 09:00:00', 'Africa/Mogadishu'),
        'ends_at' => Carbon::parse('2026-09-02 09:30:00', 'Africa/Mogadishu'),
    ]);

    $this->actingAs($receptionist)
        ->post(route('appointments.store'), validAppointmentPayload([
            'patient_id' => $patientB->id,
            'dentist_id' => $dentist->id,
            'chair_id' => $chairB->id,
            'starts_at' => '2026-09-02T09:15',
            'duration_minutes' => 30,
        ]))
        ->assertSessionHasErrors('starts_at');

    expect(Appointment::query()->count())->toBe(1);
});

test('chair overlap returns 422', function () {
    $receptionist = User::factory()->receptionist()->create();
    $dentistA = Dentist::factory()->withDefaultChair()->create();
    $dentistB = Dentist::factory()->withDefaultChair()->create();
    $chair = Chair::factory()->create();
    $patientA = Patient::factory()->create();
    $patientB = Patient::factory()->create();

    Appointment::factory()->create([
        'patient_id' => $patientA->id,
        'dentist_id' => $dentistA->id,
        'chair_id' => $chair->id,
        'starts_at' => Carbon::parse('2026-09-02 10:00:00', 'Africa/Mogadishu'),
        'ends_at' => Carbon::parse('2026-09-02 10:30:00', 'Africa/Mogadishu'),
    ]);

    $this->actingAs($receptionist)
        ->post(route('appointments.store'), validAppointmentPayload([
            'patient_id' => $patientB->id,
            'dentist_id' => $dentistB->id,
            'chair_id' => $chair->id,
            'starts_at' => '2026-09-02T10:15',
            'duration_minutes' => 30,
        ]))
        ->assertSessionHasErrors('starts_at');
});

test('booking on friday returns 422', function () {
    Carbon::setTestNow(Carbon::parse('2026-09-04 09:00:00', 'Africa/Mogadishu'));

    $receptionist = User::factory()->receptionist()->create();

    $this->actingAs($receptionist)
        ->post(route('appointments.store'), validAppointmentPayload([
            'starts_at' => '2026-09-04T09:00',
        ]))
        ->assertSessionHasErrors('starts_at');

    expect(Appointment::query()->count())->toBe(0);
});

test('booking outside working hours returns 422', function () {
    $receptionist = User::factory()->receptionist()->create();

    $this->actingAs($receptionist)
        ->post(route('appointments.store'), validAppointmentPayload([
            'starts_at' => '2026-09-02T07:30',
            'duration_minutes' => 30,
        ]))
        ->assertSessionHasErrors('starts_at');
});

test('cancelled appointment does not block a new booking in the same slot', function () {
    $receptionist = User::factory()->receptionist()->create();
    $payload = validAppointmentPayload();

    $existing = Appointment::factory()->cancelled()->create([
        'patient_id' => $payload['patient_id'],
        'dentist_id' => $payload['dentist_id'],
        'chair_id' => $payload['chair_id'],
        'starts_at' => Carbon::parse('2026-09-02 09:00:00', 'Africa/Mogadishu'),
        'ends_at' => Carbon::parse('2026-09-02 09:30:00', 'Africa/Mogadishu'),
    ]);

    $this->actingAs($receptionist)
        ->post(route('appointments.store'), $payload)
        ->assertRedirect(route('appointments.index', ['date' => '2026-09-02']));

    expect(Appointment::query()->where('status', AppointmentStatus::Scheduled)->count())->toBe(1);
    expect($existing->fresh()->status)->toBe(AppointmentStatus::Cancelled);
});

test('nurse can check in but cannot book appointments', function () {
    $nurse = User::factory()->nurse()->create();
    $appointment = Appointment::factory()->create([
        'starts_at' => Carbon::parse('2026-09-02 11:00:00', 'Africa/Mogadishu'),
        'ends_at' => Carbon::parse('2026-09-02 11:30:00', 'Africa/Mogadishu'),
    ]);

    $this->actingAs($nurse)
        ->post(route('appointments.store'), validAppointmentPayload())
        ->assertForbidden();

    $this->actingAs($nurse)
        ->post(route('appointments.cancel', $appointment))
        ->assertForbidden();

    $this->actingAs($nurse)
        ->post(route('appointments.check-in', $appointment))
        ->assertRedirect(route('appointments.index', ['date' => '2026-09-02']));

    expect($appointment->fresh()->status)->toBe(AppointmentStatus::CheckedIn);
});

test('dentist cannot mutate appointments', function () {
    $dentistUser = User::factory()->dentist()->create();
    $appointment = Appointment::factory()->create();

    $this->actingAs($dentistUser)
        ->post(route('appointments.store'), validAppointmentPayload())
        ->assertForbidden();

    $this->actingAs($dentistUser)
        ->put(route('appointments.update', $appointment), validAppointmentPayload())
        ->assertForbidden();

    $this->actingAs($dentistUser)
        ->post(route('appointments.cancel', $appointment))
        ->assertForbidden();

    $this->actingAs($dentistUser)
        ->post(route('appointments.check-in', $appointment))
        ->assertForbidden();
});

test('cancel retains previous times in revisions', function () {
    $receptionist = User::factory()->receptionist()->create();
    $appointment = Appointment::factory()->create([
        'starts_at' => Carbon::parse('2026-09-02 14:00:00', 'Africa/Mogadishu'),
        'ends_at' => Carbon::parse('2026-09-02 14:30:00', 'Africa/Mogadishu'),
    ]);

    $startsAt = $appointment->starts_at->copy();
    $endsAt = $appointment->ends_at->copy();

    $this->actingAs($receptionist)
        ->post(route('appointments.cancel', $appointment))
        ->assertRedirect(route('appointments.index', ['date' => '2026-09-02']));

    $this->assertDatabaseHas('appointment_revisions', [
        'appointment_id' => $appointment->id,
        'action' => 'cancel',
    ]);

    $revision = AppointmentRevision::query()->first();

    expect($revision->previous_starts_at->equalTo($startsAt))->toBeTrue();
    expect($revision->previous_ends_at->equalTo($endsAt))->toBeTrue();
    expect($appointment->fresh()->status)->toBe(AppointmentStatus::Cancelled);
});

test('reschedule retains previous times in revisions', function () {
    $receptionist = User::factory()->receptionist()->create();
    $appointment = Appointment::factory()->create([
        'starts_at' => Carbon::parse('2026-09-02 15:00:00', 'Africa/Mogadishu'),
        'ends_at' => Carbon::parse('2026-09-02 15:30:00', 'Africa/Mogadishu'),
    ]);

    $previousStartsAt = $appointment->starts_at->copy();
    $previousEndsAt = $appointment->ends_at->copy();

    $this->actingAs($receptionist)
        ->put(route('appointments.update', $appointment), [
            'starts_at' => '2026-09-02T16:00',
            'duration_minutes' => 30,
        ])
        ->assertRedirect(route('appointments.index', ['date' => '2026-09-02']));

    $this->assertDatabaseHas('appointment_revisions', [
        'appointment_id' => $appointment->id,
        'action' => 'reschedule',
    ]);

    $revision = AppointmentRevision::query()->first();

    expect($revision->previous_starts_at->equalTo($previousStartsAt))->toBeTrue();
    expect($revision->previous_ends_at->equalTo($previousEndsAt))->toBeTrue();
    expect($appointment->fresh()->status)->toBe(AppointmentStatus::Rescheduled);
});

test('thursday booking after 13:00 returns 422', function () {
    Carbon::setTestNow(Carbon::parse('2026-09-03 09:00:00', 'Africa/Mogadishu'));

    $receptionist = User::factory()->receptionist()->create();

    $this->actingAs($receptionist)
        ->post(route('appointments.store'), validAppointmentPayload([
            'starts_at' => '2026-09-03T12:45',
            'duration_minutes' => 30,
        ]))
        ->assertSessionHasErrors('starts_at');
});

test('index exposes working hours for selected date', function () {
    $receptionist = User::factory()->receptionist()->create();
    $dentist = Dentist::factory()->withDefaultChair()->create();

    $this->actingAs($receptionist)
        ->get(route('appointments.index', ['date' => '2026-09-02']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->missing('patients')
            ->where('workingHours.opens_at', '08:00')
            ->where('workingHours.closes_at', '18:00')
            ->where('workingHours.is_closed', false)
            ->has('columns', 1));

    Carbon::setTestNow(Carbon::parse('2026-09-04 09:00:00', 'Africa/Mogadishu'));

    $this->actingAs($receptionist)
        ->get(route('appointments.index', ['date' => '2026-09-04']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('workingHours.is_closed', true));

    Carbon::setTestNow(Carbon::parse('2026-09-03 09:00:00', 'Africa/Mogadishu'));

    $this->actingAs($receptionist)
        ->get(route('appointments.index', ['date' => '2026-09-03']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('workingHours.closes_at', '13:00'));
});

test('admin can book an appointment', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('appointments.store'), validAppointmentPayload())
        ->assertRedirect(route('appointments.index', ['date' => '2026-09-02']));

    expect(Appointment::query()->count())->toBe(1);
});

test('no show appointment does not block a new booking in the same slot', function () {
    $receptionist = User::factory()->receptionist()->create();
    $payload = validAppointmentPayload();

    $existing = Appointment::factory()->noShow()->create([
        'patient_id' => $payload['patient_id'],
        'dentist_id' => $payload['dentist_id'],
        'chair_id' => $payload['chair_id'],
        'starts_at' => Carbon::parse('2026-09-02 09:00:00', 'Africa/Mogadishu'),
        'ends_at' => Carbon::parse('2026-09-02 09:30:00', 'Africa/Mogadishu'),
    ]);

    $this->actingAs($receptionist)
        ->post(route('appointments.store'), $payload)
        ->assertRedirect(route('appointments.index', ['date' => '2026-09-02']));

    expect(Appointment::query()->where('status', AppointmentStatus::Scheduled)->count())->toBe(1);
    expect($existing->fresh()->status)->toBe(AppointmentStatus::NoShow);
});

test('archived patient cannot be booked', function () {
    $receptionist = User::factory()->receptionist()->create();
    $archivedPatient = Patient::factory()->archived()->create();
    $dentist = Dentist::factory()->withDefaultChair()->create();
    $chair = Chair::factory()->create();

    $this->actingAs($receptionist)
        ->post(route('appointments.store'), validAppointmentPayload([
            'patient_id' => $archivedPatient->id,
            'dentist_id' => $dentist->id,
            'chair_id' => $chair->id,
        ]))
        ->assertSessionHasErrors('patient_id');

    expect(Appointment::query()->count())->toBe(0);
});

test('inactive dentist cannot be booked', function () {
    $receptionist = User::factory()->receptionist()->create();
    $patient = Patient::factory()->create();
    $dentist = Dentist::factory()->inactive()->withDefaultChair()->create();
    $chair = Chair::factory()->create();

    $this->actingAs($receptionist)
        ->post(route('appointments.store'), validAppointmentPayload([
            'patient_id' => $patient->id,
            'dentist_id' => $dentist->id,
            'chair_id' => $chair->id,
        ]))
        ->assertSessionHasErrors('dentist_id');

    expect(Appointment::query()->count())->toBe(0);
});

test('inactive chair cannot be booked', function () {
    $receptionist = User::factory()->receptionist()->create();
    $patient = Patient::factory()->create();
    $dentist = Dentist::factory()->withDefaultChair()->create();
    $chair = Chair::factory()->inactive()->create();

    $this->actingAs($receptionist)
        ->post(route('appointments.store'), validAppointmentPayload([
            'patient_id' => $patient->id,
            'dentist_id' => $dentist->id,
            'chair_id' => $chair->id,
        ]))
        ->assertSessionHasErrors('chair_id');

    expect(Appointment::query()->count())->toBe(0);
});

test('check in rejects appointments that are not scheduled or confirmed', function () {
    $nurse = User::factory()->nurse()->create();

    $checkedIn = Appointment::factory()->create([
        'status' => AppointmentStatus::CheckedIn,
        'starts_at' => Carbon::parse('2026-09-02 11:00:00', 'Africa/Mogadishu'),
        'ends_at' => Carbon::parse('2026-09-02 11:30:00', 'Africa/Mogadishu'),
    ]);

    $this->actingAs($nurse)
        ->post(route('appointments.check-in', $checkedIn))
        ->assertSessionHasErrors('status');

    expect($checkedIn->fresh()->status)->toBe(AppointmentStatus::CheckedIn);

    $inProgress = Appointment::factory()->create([
        'status' => AppointmentStatus::InProgress,
        'starts_at' => Carbon::parse('2026-09-02 12:00:00', 'Africa/Mogadishu'),
        'ends_at' => Carbon::parse('2026-09-02 12:30:00', 'Africa/Mogadishu'),
    ]);

    $this->actingAs($nurse)
        ->post(route('appointments.check-in', $inProgress))
        ->assertSessionHasErrors('status');

    expect($inProgress->fresh()->status)->toBe(AppointmentStatus::InProgress);
});

test('updating appointment with blank duration preserves existing length', function () {
    $receptionist = User::factory()->receptionist()->create();
    $appointment = Appointment::factory()->create([
        'starts_at' => Carbon::parse('2026-09-02 09:00:00', 'Africa/Mogadishu'),
        'ends_at' => Carbon::parse('2026-09-02 10:00:00', 'Africa/Mogadishu'),
    ]);

    $this->actingAs($receptionist)
        ->put(route('appointments.update', $appointment), [
            'reason' => 'Updated reason',
            'duration_minutes' => '',
        ])
        ->assertRedirect();

    $appointment->refresh();

    expect($appointment->ends_at->equalTo(Carbon::parse('2026-09-02 10:00:00', 'Africa/Mogadishu')))->toBeTrue();
});

test('inactive fee item cannot be assigned to appointment', function () {
    $receptionist = User::factory()->receptionist()->create();
    $feeItem = FeeItem::factory()->inactive()->create();
    $appointment = Appointment::factory()->create();

    $this->actingAs($receptionist)
        ->put(route('appointments.update', $appointment), [
            'fee_item_id' => $feeItem->id,
        ])
        ->assertSessionHasErrors('fee_item_id');
});
