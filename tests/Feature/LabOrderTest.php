<?php

use App\Enums\ClinicRole;
use App\Enums\LabOrderStatus;
use App\Models\Dentist;
use App\Models\LabOrder;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Support\Carbon;

beforeEach(function () {
    Carbon::setTestNow(Carbon::parse('2026-09-04 10:00:00', 'Africa/Mogadishu'));
});

afterEach(function () {
    Carbon::setTestNow();
});

function validLabOrderPayload(array $overrides = []): array
{
    $patient = Patient::factory()->create();
    $dentist = Dentist::factory()->create();

    return array_merge([
        'patient_id' => $patient->id,
        'dentist_id' => $dentist->id,
        'treatment_id' => null,
        'encounter_id' => null,
        'description' => 'Zirconia crown #36',
        'notes' => 'Shade A2',
        'due_date' => '2026-09-18',
    ], $overrides);
}

test('authorized roles can create lab orders with LAB number format', function (ClinicRole $role) {
    $user = User::factory()->role($role)->create();
    $payload = validLabOrderPayload();

    $this->actingAs($user)
        ->post(route('lab.store'), $payload)
        ->assertRedirect();

    $order = LabOrder::query()->first();

    expect($order)->not->toBeNull()
        ->and($order->number)->toMatch('/^LAB-\d{4}-\d{5}$/')
        ->and($order->status)->toBe(LabOrderStatus::Ordered)
        ->and($order->description)->toBe('Zirconia crown #36')
        ->and($order->created_by)->toBe($user->id);
})->with([
    'admin' => ClinicRole::Admin,
    'dentist' => ClinicRole::Dentist,
    'lab' => ClinicRole::Lab,
]);

test('receptionist cannot access lab module routes', function () {
    $receptionist = User::factory()->receptionist()->create();
    $order = LabOrder::factory()->create();

    $this->actingAs($receptionist)
        ->get(route('lab.index'))
        ->assertForbidden();

    $this->actingAs($receptionist)
        ->get(route('lab.create'))
        ->assertForbidden();

    $this->actingAs($receptionist)
        ->post(route('lab.store'), validLabOrderPayload())
        ->assertForbidden();

    $this->actingAs($receptionist)
        ->get(route('lab.show', $order))
        ->assertForbidden();

    $this->actingAs($receptionist)
        ->post(route('lab.transition', $order), [
            'status' => LabOrderStatus::ReceivedByLab->value,
        ])
        ->assertForbidden();
});

test('lab order status transitions follow DCMS workflow', function () {
    $labUser = User::factory()->lab()->create();
    $order = LabOrder::factory()->create([
        'status' => LabOrderStatus::Ordered,
    ]);

    $this->actingAs($labUser)
        ->post(route('lab.transition', $order), [
            'status' => LabOrderStatus::ReceivedByLab->value,
        ])
        ->assertRedirect(route('lab.show', $order));

    expect($order->fresh()->status)->toBe(LabOrderStatus::ReceivedByLab);

    $this->actingAs($labUser)
        ->post(route('lab.transition', $order), [
            'status' => LabOrderStatus::InProduction->value,
        ])
        ->assertRedirect(route('lab.show', $order));

    expect($order->fresh()->status)->toBe(LabOrderStatus::InProduction);

    $this->actingAs($labUser)
        ->post(route('lab.transition', $order), [
            'status' => LabOrderStatus::Ready->value,
        ])
        ->assertRedirect(route('lab.show', $order));

    expect($order->fresh()->status)->toBe(LabOrderStatus::Ready);
});

test('invalid lab order status transition returns validation error', function () {
    $admin = User::factory()->admin()->create();
    $order = LabOrder::factory()->create([
        'status' => LabOrderStatus::Ordered,
    ]);

    $this->actingAs($admin)
        ->from(route('lab.show', $order))
        ->post(route('lab.transition', $order), [
            'status' => LabOrderStatus::Ready->value,
        ])
        ->assertSessionHasErrors('status');

    expect($order->fresh()->status)->toBe(LabOrderStatus::Ordered);
});

test('terminal lab orders cannot be transitioned', function () {
    $admin = User::factory()->admin()->create();
    $order = LabOrder::factory()->withStatus(LabOrderStatus::Fitted)->create();

    $this->actingAs($admin)
        ->post(route('lab.transition', $order), [
            'status' => LabOrderStatus::Cancelled->value,
        ])
        ->assertForbidden();
});

test('lab orders do not create invoices or payment records', function () {
    $dentistUser = User::factory()->dentist()->create();

    $this->actingAs($dentistUser)
        ->post(route('lab.store'), validLabOrderPayload())
        ->assertRedirect();

    expect(LabOrder::query()->count())->toBe(1);
    $this->assertDatabaseCount('invoices', 0);
    $this->assertDatabaseCount('payments', 0);
});

test('lab role can view lab index with create permission', function () {
    $labUser = User::factory()->lab()->create();

    $this->actingAs($labUser)
        ->get(route('lab.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('lab/Index')
            ->where('canCreate', true));
});

test('lab module appears in allowed modules for authorized roles only', function (ClinicRole $role, bool $canView) {
    expect($role->canViewModule('lab'))->toBe($canView);
})->with([
    'admin' => [ClinicRole::Admin, true],
    'dentist' => [ClinicRole::Dentist, true],
    'lab' => [ClinicRole::Lab, true],
    'receptionist' => [ClinicRole::Receptionist, false],
    'nurse' => [ClinicRole::Nurse, false],
    'accountant' => [ClinicRole::Accountant, false],
]);
