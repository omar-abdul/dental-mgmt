<?php

use App\Enums\AppointmentStatus;
use App\Enums\ClinicRole;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\TreatmentStatus;
use App\Models\Appointment;
use App\Models\Chair;
use App\Models\Dentist;
use App\Models\InventoryItem;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Treatment;
use App\Models\TreatmentProcedure;
use App\Models\User;
use Illuminate\Support\Carbon;

function reportsFrozenTime(): Carbon
{
    return Carbon::parse('2026-01-07 09:00:00', 'Africa/Mogadishu');
}

function reportsDateRange(): array
{
    return [
        'from' => '2026-01-01',
        'to' => '2026-01-07',
    ];
}

function seedReportsDataset(): array
{
    $rangeStart = reportsFrozenTime()->copy()->startOfMonth()->startOfDay();
    $patient = Patient::factory()->create([
        'created_at' => $rangeStart->copy()->addDay(),
        'updated_at' => $rangeStart->copy()->addDay(),
    ]);
    $dentist = Dentist::factory()->create();
    $otherDentist = Dentist::factory()->create();
    $chair = Chair::factory()->create();

    Appointment::factory()->forPatient($patient)->forDentist($dentist)->forChair($chair)->create([
        'starts_at' => $rangeStart->copy()->addDays(2)->setTime(10, 0),
        'ends_at' => $rangeStart->copy()->addDays(2)->setTime(10, 30),
        'status' => AppointmentStatus::Scheduled,
    ]);

    Appointment::factory()->forPatient($patient)->forDentist($otherDentist)->forChair($chair)->cancelled()->create([
        'starts_at' => $rangeStart->copy()->addDays(3)->setTime(11, 0),
        'ends_at' => $rangeStart->copy()->addDays(3)->setTime(11, 30),
    ]);

    $invoice = Invoice::factory()->forPatient($patient)->create([
        'status' => InvoiceStatus::Issued,
        'balance_cents' => 5000,
        'issued_at' => $rangeStart->copy()->addDay(),
    ]);

    $inRangePayment = Payment::factory()->forInvoice($invoice)->create([
        'amount_cents' => 3000,
        'method' => PaymentMethod::Cash,
        'status' => PaymentStatus::Completed,
        'paid_at' => $rangeStart->copy()->addDays(2)->setTime(12, 0),
    ]);

    $outOfRangePayment = Payment::factory()->forInvoice($invoice)->create([
        'amount_cents' => 9000,
        'method' => PaymentMethod::Card,
        'status' => PaymentStatus::Completed,
        'paid_at' => $rangeStart->copy()->subDay(),
    ]);

    Payment::factory()->forInvoice($invoice)->create([
        'amount_cents' => 1000,
        'status' => PaymentStatus::Pending,
        'paid_at' => $rangeStart->copy()->addDays(2)->setTime(13, 0),
    ]);

    InventoryItem::factory()->create(['quantity' => 3, 'reorder_level' => 5]);
    InventoryItem::factory()->create(['quantity' => 20, 'reorder_level' => 5]);

    $ownTreatment = Treatment::factory()->forPatient($patient)->forDentist($dentist)->create([
        'diagnosed_at' => $rangeStart->copy()->addDays(2)->setTime(9, 0),
        'status' => TreatmentStatus::Completed,
    ]);

    Treatment::factory()->forPatient($patient)->forDentist($otherDentist)->create([
        'diagnosed_at' => $rangeStart->copy()->addDays(2)->setTime(9, 30),
        'status' => TreatmentStatus::Planned,
    ]);

    TreatmentProcedure::factory()->create([
        'treatment_id' => $ownTreatment->id,
        'fee_cents' => 4500,
        'quantity' => 1,
    ]);

    return [
        'dentist' => $dentist,
        'otherDentist' => $otherDentist,
        'inRangePayment' => $inRangePayment,
        'outOfRangePayment' => $outOfRangePayment,
        'ownTreatment' => $ownTreatment,
    ];
}

test('guest is redirected to login when visiting reports hub', function () {
    $this->get(route('reports.index'))
        ->assertRedirectToRoute('login');
});

test('each clinic role can visit the reports hub', function (ClinicRole $role) {
    $user = User::factory()->role($role)->create();

    $this->actingAs($user)
        ->get(route('reports.index', reportsDateRange()))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('reports/Index'));
})->with([
    'admin' => ClinicRole::Admin,
    'dentist' => ClinicRole::Dentist,
    'receptionist' => ClinicRole::Receptionist,
    'nurse' => ClinicRole::Nurse,
    'accountant' => ClinicRole::Accountant,
    'lab' => ClinicRole::Lab,
]);

test('admin reports hub summary matches seeded counts and completed payment cents', function () {
    $this->travelTo(reportsFrozenTime());

    seedReportsDataset();
    $admin = User::factory()->admin()->create();

    $expectedPaymentsCents = (int) Payment::query()
        ->where('status', PaymentStatus::Completed)
        ->where('paid_at', '>=', Carbon::parse('2026-01-01', 'Africa/Mogadishu')->startOfDay())
        ->where('paid_at', '<=', Carbon::parse('2026-01-07', 'Africa/Mogadishu')->endOfDay())
        ->sum('amount_cents');

    $this->actingAs($admin)
        ->get(route('reports.index', reportsDateRange()))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('reports/Index')
            ->where('canViewFinance', true)
            ->where('summary.appointments', 1)
            ->where('summary.registrations', 1)
            ->where('summary.payments_cents', $expectedPaymentsCents)
            ->where('summary.outstanding_cents', 5000)
            ->has('reports', 7));
});

test('lab reports hub omits finance summary and finance report cards', function () {
    $this->travelTo(reportsFrozenTime());

    seedReportsDataset();
    $labUser = User::factory()->lab()->create();

    $this->actingAs($labUser)
        ->get(route('reports.index', reportsDateRange()))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('canViewFinance', false)
            ->where('summary.payments_cents', null)
            ->where('summary.outstanding_cents', null)
            ->has('reports', 5)
            ->where('reports.0.finance', false));
});

test('accountant can view finance reports', function () {
    $this->travelTo(reportsFrozenTime());

    seedReportsDataset();
    $accountant = User::factory()->accountant()->create();

    $this->actingAs($accountant)
        ->get(route('reports.payments', reportsDateRange()))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('reports/Payments'));

    $this->actingAs($accountant)
        ->get(route('reports.outstanding-balances', reportsDateRange()))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('reports/OutstandingBalances'));
});

test('non-finance roles receive 403 on finance report routes', function (ClinicRole $role) {
    $this->travelTo(reportsFrozenTime());

    $user = User::factory()->role($role)->create();

    $this->actingAs($user)
        ->get(route('reports.payments', reportsDateRange()))
        ->assertForbidden();

    $this->actingAs($user)
        ->get(route('reports.outstanding-balances', reportsDateRange()))
        ->assertForbidden();
})->with([
    'dentist' => ClinicRole::Dentist,
    'receptionist' => ClinicRole::Receptionist,
    'nurse' => ClinicRole::Nurse,
    'lab' => ClinicRole::Lab,
]);

test('daily appointments report excludes vacated statuses', function () {
    $this->travelTo(reportsFrozenTime());

    seedReportsDataset();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('reports.daily-appointments', reportsDateRange()))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('reports/DailyAppointments')
            ->where('report.total', 1));
});

test('patient registration report counts patients created in range', function () {
    $this->travelTo(reportsFrozenTime());

    seedReportsDataset();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('reports.patient-registration', reportsDateRange()))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('reports/PatientRegistration')
            ->where('report.total', 1));
});

test('payments report totals match completed payments in range only', function () {
    $this->travelTo(reportsFrozenTime());

    seedReportsDataset();
    $admin = User::factory()->admin()->create();

    $expectedTotal = (int) Payment::query()
        ->where('status', PaymentStatus::Completed)
        ->where('paid_at', '>=', Carbon::parse('2026-01-01', 'Africa/Mogadishu')->startOfDay())
        ->where('paid_at', '<=', Carbon::parse('2026-01-07', 'Africa/Mogadishu')->endOfDay())
        ->sum('amount_cents');

    $this->actingAs($admin)
        ->get(route('reports.payments', reportsDateRange()))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('reports/Payments')
            ->where('report.total_cents', $expectedTotal)
            ->where('report.payment_count', 1)
            ->where('report.by_method.0.method', 'cash')
            ->where('report.by_method.0.total_cents', 3000));
});

test('outstanding balances report sums open invoice balances', function () {
    $this->travelTo(reportsFrozenTime());

    seedReportsDataset();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('reports.outstanding-balances', reportsDateRange()))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('reports/OutstandingBalances')
            ->where('report.invoice_count', 1)
            ->where('report.total_balance_cents', 5000));
});

test('outstanding balances report filters by invoice issued date', function () {
    $this->travelTo(reportsFrozenTime());

    seedReportsDataset();
    $admin = User::factory()->admin()->create();

    Invoice::factory()->forPatient(Patient::factory()->create())->create([
        'status' => InvoiceStatus::Issued,
        'balance_cents' => 9000,
        'issued_at' => Carbon::parse('2025-12-15', 'Africa/Mogadishu'),
    ]);

    $this->actingAs($admin)
        ->get(route('reports.outstanding-balances', reportsDateRange()))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('reports/OutstandingBalances')
            ->where('report.invoice_count', 1)
            ->where('report.total_balance_cents', 5000));
});

test('inventory stock and low stock reports return expected counts', function () {
    $this->travelTo(reportsFrozenTime());

    seedReportsDataset();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('reports.inventory-stock', reportsDateRange()))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('reports/InventoryStock')
            ->where('report.total_items', 2));

    $this->actingAs($admin)
        ->get(route('reports.low-stock', reportsDateRange()))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('reports/LowStock')
            ->where('report.total', 1));
});

test('dentist treatment statistics are scoped to the logged-in dentist', function () {
    $this->travelTo(reportsFrozenTime());

    $dentistUser = User::factory()->dentist()->create();
    $dentist = Dentist::factory()->forUser($dentistUser)->create();
    $otherDentist = Dentist::factory()->create();
    $patient = Patient::factory()->create();
    $rangeStart = reportsFrozenTime()->copy()->startOfMonth()->startOfDay();

    $ownTreatment = Treatment::factory()->forPatient($patient)->forDentist($dentist)->create([
        'diagnosed_at' => $rangeStart->copy()->addDays(2)->setTime(9, 0),
        'status' => TreatmentStatus::Completed,
    ]);

    Treatment::factory()->forPatient($patient)->forDentist($otherDentist)->create([
        'diagnosed_at' => $rangeStart->copy()->addDays(2)->setTime(9, 30),
        'status' => TreatmentStatus::Planned,
    ]);

    TreatmentProcedure::factory()->create([
        'treatment_id' => $ownTreatment->id,
        'fee_cents' => 4500,
        'quantity' => 1,
    ]);

    $this->actingAs($dentistUser)
        ->get(route('reports.treatment-statistics', reportsDateRange()))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('reports/TreatmentStatistics')
            ->where('report.total', 1)
            ->where('report.procedure_count', 1)
            ->where('report.procedure_fees_cents', 4500));

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('reports.treatment-statistics', reportsDateRange()))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('report.total', 2)
            ->where('report.procedure_count', 1));
});

test('dentist daily appointments report is scoped to their schedule', function () {
    $this->travelTo(reportsFrozenTime());

    $dentistUser = User::factory()->dentist()->create();
    $dentist = Dentist::factory()->forUser($dentistUser)->create();
    $otherDentist = Dentist::factory()->create();
    $patient = Patient::factory()->create();
    $chair = Chair::factory()->create();
    $rangeStart = reportsFrozenTime()->copy()->startOfMonth()->startOfDay();

    Appointment::factory()->forPatient($patient)->forDentist($dentist)->forChair($chair)->create([
        'starts_at' => $rangeStart->copy()->addDays(2)->setTime(10, 0),
        'ends_at' => $rangeStart->copy()->addDays(2)->setTime(10, 30),
        'status' => AppointmentStatus::Scheduled,
    ]);

    Appointment::factory()->forPatient($patient)->forDentist($otherDentist)->forChair($chair)->create([
        'starts_at' => $rangeStart->copy()->addDays(2)->setTime(11, 0),
        'ends_at' => $rangeStart->copy()->addDays(2)->setTime(11, 30),
        'status' => AppointmentStatus::Scheduled,
    ]);

    $this->actingAs($dentistUser)
        ->get(route('reports.daily-appointments', reportsDateRange()))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('report.total', 1));
});
