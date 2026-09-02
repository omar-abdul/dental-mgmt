<?php

use App\Enums\AppointmentStatus;
use App\Enums\ClinicRole;
use App\Enums\InvoiceStatus;
use App\Enums\PatientStatus;
use App\Models\ActivityLog;
use App\Models\Appointment;
use App\Models\Chair;
use App\Models\Dentist;
use App\Models\InventoryItem;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Support\Carbon;

function dashboardFrozenTime(): Carbon
{
    return Carbon::parse('2026-01-07 09:00:00', 'Africa/Mogadishu');
}

function seedDashboardMetricsDataset(): array
{
    $patient = Patient::factory()->create();
    $dentist = Dentist::factory()->create();
    $chair = Chair::factory()->create();

    $today = dashboardFrozenTime()->copy()->startOfDay();

    Appointment::factory()->forPatient($patient)->forDentist($dentist)->forChair($chair)->create([
        'starts_at' => $today->copy()->setTime(8, 0),
        'ends_at' => $today->copy()->setTime(8, 30),
        'status' => AppointmentStatus::Scheduled,
    ]);

    $upcomingSoon = Appointment::factory()->forPatient($patient)->forDentist($dentist)->forChair($chair)->create([
        'starts_at' => $today->copy()->setTime(11, 0),
        'ends_at' => $today->copy()->setTime(11, 30),
        'status' => AppointmentStatus::Confirmed,
    ]);

    $upcomingLater = Appointment::factory()->forPatient($patient)->forDentist($dentist)->forChair($chair)->create([
        'starts_at' => $today->copy()->setTime(14, 0),
        'ends_at' => $today->copy()->setTime(14, 30),
        'status' => AppointmentStatus::Scheduled,
    ]);

    Appointment::factory()->forPatient($patient)->forDentist($dentist)->forChair($chair)->cancelled()->create([
        'starts_at' => $today->copy()->setTime(10, 0),
        'ends_at' => $today->copy()->setTime(10, 30),
    ]);

    Appointment::factory()->forPatient($patient)->forDentist($dentist)->forChair($chair)->noShow()->create([
        'starts_at' => $today->copy()->setTime(10, 30),
        'ends_at' => $today->copy()->setTime(11, 0),
    ]);

    Appointment::factory()->forPatient($patient)->forDentist($dentist)->forChair($chair)->create([
        'starts_at' => $today->copy()->setTime(12, 0),
        'ends_at' => $today->copy()->setTime(12, 30),
        'status' => AppointmentStatus::Rescheduled,
    ]);

    Appointment::factory()->forPatient($patient)->forDentist($dentist)->forChair($chair)->cancelled()->create([
        'starts_at' => $today->copy()->setTime(15, 0),
        'ends_at' => $today->copy()->setTime(15, 30),
    ]);

    Appointment::factory()->forPatient($patient)->forDentist($dentist)->forChair($chair)->create([
        'starts_at' => $today->copy()->subDays(2)->setTime(10, 0),
        'ends_at' => $today->copy()->subDays(2)->setTime(10, 30),
        'status' => AppointmentStatus::Scheduled,
    ]);

    Patient::factory()->count(2)->create(['status' => PatientStatus::Active]);
    Patient::factory()->create(['status' => PatientStatus::Inactive]);
    Patient::factory()->archived()->create();

    Invoice::factory()->forPatient($patient)->create(['status' => InvoiceStatus::Issued]);
    Invoice::factory()->forPatient($patient)->create(['status' => InvoiceStatus::PartiallyPaid]);
    Invoice::factory()->forPatient($patient)->create(['status' => InvoiceStatus::Paid]);

    InventoryItem::factory()->create(['quantity' => 3, 'reorder_level' => 5]);
    InventoryItem::factory()->create(['quantity' => 0, 'reorder_level' => 5]);
    InventoryItem::factory()->create(['quantity' => 20, 'reorder_level' => 5]);

    $olderActivity = ActivityLog::factory()->create([
        'created_at' => dashboardFrozenTime()->copy()->subDay(),
        'updated_at' => dashboardFrozenTime()->copy()->subDay(),
    ]);

    $newerActivity = ActivityLog::factory()->create([
        'created_at' => dashboardFrozenTime()->copy()->subHour(),
        'updated_at' => dashboardFrozenTime()->copy()->subHour(),
    ]);

    return [
        'upcomingSoon' => $upcomingSoon,
        'upcomingLater' => $upcomingLater,
        'olderActivity' => $olderActivity,
        'newerActivity' => $newerActivity,
    ];
}

test('guests are redirected to the login page', function () {
    $this->get(route('dashboard'))->assertRedirectToRoute('login');
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk();
});

test('each clinic role can visit the dashboard', function (ClinicRole $role) {
    $user = User::factory()->role($role)->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk();
})->with([
    'admin' => ClinicRole::Admin,
    'dentist' => ClinicRole::Dentist,
    'receptionist' => ClinicRole::Receptionist,
    'nurse' => ClinicRole::Nurse,
    'accountant' => ClinicRole::Accountant,
    'lab' => ClinicRole::Lab,
]);

test('admin dashboard props match seeded counts and lists', function () {
    $this->travelTo(dashboardFrozenTime());

    $admin = User::factory()->admin()->create();
    $seed = seedDashboardMetricsDataset();

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->where('kpis.todays_appointments', 3)
            ->where('kpis.active_patients', 3)
            ->where('kpis.unpaid_invoices', 2)
            ->where('kpis.low_stock_items', 1)
            ->where('weekly_visits.2.key', 'wed')
            ->where('weekly_visits.2.count', 3)
            ->where('weekly_visits.0.count', 1)
            ->has('upcoming', 2)
            ->where('upcoming.0.id', $seed['upcomingSoon']->id)
            ->where('upcoming.1.id', $seed['upcomingLater']->id)
            ->where('recent_activity.0.id', $seed['newerActivity']->id)
            ->where('recent_activity.1.id', $seed['olderActivity']->id));
});

test('todays appointment count ignores vacated statuses', function () {
    $this->travelTo(dashboardFrozenTime());

    $admin = User::factory()->admin()->create();
    $patient = Patient::factory()->create();
    $dentist = Dentist::factory()->create();
    $chair = Chair::factory()->create();
    $today = dashboardFrozenTime()->copy()->startOfDay();

    Appointment::factory()->forPatient($patient)->forDentist($dentist)->forChair($chair)->create([
        'starts_at' => $today->copy()->setTime(13, 0),
        'ends_at' => $today->copy()->setTime(13, 30),
        'status' => AppointmentStatus::Scheduled,
    ]);

    Appointment::factory()->forPatient($patient)->forDentist($dentist)->forChair($chair)->cancelled()->create([
        'starts_at' => $today->copy()->setTime(13, 30),
        'ends_at' => $today->copy()->setTime(14, 0),
    ]);

    Appointment::factory()->forPatient($patient)->forDentist($dentist)->forChair($chair)->noShow()->create([
        'starts_at' => $today->copy()->setTime(14, 0),
        'ends_at' => $today->copy()->setTime(14, 30),
    ]);

    Appointment::factory()->forPatient($patient)->forDentist($dentist)->forChair($chair)->create([
        'starts_at' => $today->copy()->setTime(14, 30),
        'ends_at' => $today->copy()->setTime(15, 0),
        'status' => AppointmentStatus::Rescheduled,
    ]);

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('kpis.todays_appointments', 1));
});

test('active patients count ignores inactive and archived patients', function () {
    $this->travelTo(dashboardFrozenTime());

    $admin = User::factory()->admin()->create();

    Patient::factory()->count(2)->create(['status' => PatientStatus::Active]);
    Patient::factory()->create(['status' => PatientStatus::Inactive]);
    Patient::factory()->archived()->create();

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('kpis.active_patients', 2));
});

test('unpaid invoice kpi counts issued and partially paid but not paid', function () {
    $this->travelTo(dashboardFrozenTime());

    $admin = User::factory()->admin()->create();

    Invoice::factory()->create(['status' => InvoiceStatus::Issued]);
    Invoice::factory()->create(['status' => InvoiceStatus::PartiallyPaid]);
    Invoice::factory()->create(['status' => InvoiceStatus::Paid]);

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('kpis.unpaid_invoices', 2));
});

test('low stock kpi counts low stock but not out of stock items', function () {
    $this->travelTo(dashboardFrozenTime());

    $admin = User::factory()->admin()->create();

    InventoryItem::factory()->create(['quantity' => 3, 'reorder_level' => 5]);
    InventoryItem::factory()->create(['quantity' => 0, 'reorder_level' => 5]);
    InventoryItem::factory()->create(['quantity' => 20, 'reorder_level' => 5]);

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('kpis.low_stock_items', 1));
});

test('lab role dashboard omits restricted kpis and upcoming patient names', function () {
    $this->travelTo(dashboardFrozenTime());

    seedDashboardMetricsDataset();

    $labUser = User::factory()->lab()->create();

    $this->actingAs($labUser)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('kpis.todays_appointments', null)
            ->where('kpis.active_patients', null)
            ->where('kpis.unpaid_invoices', null)
            ->where('kpis.low_stock_items', null)
            ->where('weekly_visits', null)
            ->where('upcoming', null)
            ->has('recent_activity'));
});

test('accountant dashboard shows unpaid invoices only', function () {
    $this->travelTo(dashboardFrozenTime());

    Invoice::factory()->create(['status' => InvoiceStatus::Issued]);
    Invoice::factory()->create(['status' => InvoiceStatus::PartiallyPaid]);

    $accountant = User::factory()->accountant()->create();

    $this->actingAs($accountant)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('kpis.unpaid_invoices', 2)
            ->where('kpis.todays_appointments', null)
            ->where('kpis.active_patients', null)
            ->where('kpis.low_stock_items', null)
            ->where('weekly_visits', null)
            ->where('upcoming', null));
});

test('nurse dashboard shows appointment patient and stock kpis but not unpaid invoices', function () {
    $this->travelTo(dashboardFrozenTime());

    seedDashboardMetricsDataset();

    $nurse = User::factory()->nurse()->create();

    $this->actingAs($nurse)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('kpis.unpaid_invoices', null)
            ->where('kpis.todays_appointments', 3)
            ->where('kpis.active_patients', 3)
            ->where('kpis.low_stock_items', 1)
            ->where('weekly_visits.2.count', 3)
            ->has('upcoming', 2));
});
