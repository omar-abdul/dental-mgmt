<?php

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Chair;
use App\Models\Dentist;
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

test('receptionist can book and check in an appointment from the day calendar', function () {
    $receptionist = User::factory()->receptionist()->create();
    $patient = Patient::factory()->create([
        'first_name' => 'Maria',
        'last_name' => 'Santos',
        'patient_number' => 'PAT-2026-00042',
        'phone' => '+252611110000',
    ]);
    $chair = Chair::factory()->create(['name' => 'Chair 1']);
    $dentist = Dentist::factory()->withDefaultChair($chair)->create([
        'display_name' => 'Dr. R. Lim',
    ]);

    $this->actingAs($receptionist);

    $page = visit(route('appointments.index', ['date' => '2026-09-02']));

    $page->assertSee('Appointments')
        ->assertSee('Dr. R. Lim')
        ->click('@book-appointment-button')
        ->assertSee('Book appointment')
        ->fill('@patient-picker-search', 'Maria')
        ->click('@patient-picker-option')
        ->select('dentist_id', (string) $dentist->id)
        ->select('chair_id', (string) $chair->id)
        ->fill('starts_at', '2026-09-02T09:00')
        ->fill('duration_minutes', '30')
        ->click('@book-appointment-submit')
        ->assertSee('Maria Santos')
        ->assertSee('scheduled')
        ->click('@check-in-appointment-button')
        ->assertSee('checked in')
        ->assertNoJavaScriptErrors();

    $appointment = Appointment::query()->first();

    expect($appointment)->not->toBeNull()
        ->and($appointment->patient_id)->toBe($patient->id)
        ->and($appointment->dentist_id)->toBe($dentist->id)
        ->and($appointment->chair_id)->toBe($chair->id)
        ->and($appointment->status)->toBe(AppointmentStatus::CheckedIn)
        ->and($appointment->number)->toBe('APT-2026-00001');
});
