<?php

use App\Enums\ClinicRole;
use App\Enums\Gender;
use App\Enums\InventoryCategory;
use App\Models\Chair;
use App\Models\Dentist;
use App\Models\InventoryItem;
use App\Models\Patient;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\WorkingHourSeeder;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;

function pickerStaffDentistPayload(string $name = 'Dr. Amina Yusuf', string $email = 'a.yusuf@goldensmile.clinic'): array
{
    return [
        'name' => $name,
        'email' => $email,
        'role' => ClinicRole::Dentist->value,
        'password' => 'password12',
        'password_confirmation' => 'password12',
    ];
}

test('a staff-created dentist appears in {page} dentist options', function (string $route, string $component, Closure $params) {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('staff.store'), pickerStaffDentistPayload())
        ->assertRedirectToRoute('staff.index');

    $dentist = User::query()->where('email', 'a.yusuf@goldensmile.clinic')->first()?->dentist;

    expect($dentist)->not->toBeNull();

    $this->actingAs($admin)
        ->get(route($route, $params($dentist)))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component($component)
            ->has('dentists', 1)
            ->where('dentists.0.id', $dentist->id)
            ->where('dentists.0.label', 'Dr. Amina Yusuf'));
})->with([
    'appointments' => ['appointments.index', 'appointments/Index', fn () => []],
    'treatments create' => ['treatments.create', 'treatments/Create', fn () => []],
    'lab create' => ['lab.create', 'lab/Create', fn () => []],
    'imaging create' => ['imaging.create', 'imaging/Create', fn () => []],
    'patient chart' => ['patients.chart', 'chart/PatientChart', fn () => [
        'patient' => Patient::factory()->create(),
    ]],
]);

test('appointments calendar columns include a staff-created dentist', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('staff.store'), pickerStaffDentistPayload())
        ->assertRedirectToRoute('staff.index');

    $dentist = User::query()->where('email', 'a.yusuf@goldensmile.clinic')->first()?->dentist;

    $this->actingAs($admin)
        ->get(route('appointments.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('appointments/Index')
            ->has('columns', 1)
            ->where('columns.0.id', $dentist->id)
            ->where('columns.0.dentist_name', 'Dr. Amina Yusuf'));
});

test('inactive dentists are omitted from dentist dropdowns', function () {
    $admin = User::factory()->admin()->create();
    Dentist::factory()->inactive()->create([
        'display_name' => 'Dr. Inactive',
    ]);

    $this->actingAs($admin)
        ->post(route('staff.store'), pickerStaffDentistPayload())
        ->assertRedirectToRoute('staff.index');

    $this->actingAs($admin)
        ->get(route('appointments.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('dentists', 1)
            ->where('dentists.0.label', 'Dr. Amina Yusuf'));
});

test('a registered patient appears in picker search', function () {
    $receptionist = User::factory()->receptionist()->create();

    $this->actingAs($receptionist)
        ->post(route('patients.store'), [
            'first_name' => 'Picker',
            'last_name' => 'Patient',
            'date_of_birth' => '1990-05-15',
            'gender' => Gender::Female->value,
            'phone' => '+252611234567',
            'email' => 'picker.patient@example.com',
        ])
        ->assertRedirect();

    $patient = Patient::query()->where('email', 'picker.patient@example.com')->first();

    expect($patient)->not->toBeNull();

    $this->actingAs($receptionist)
        ->getJson(route('patients.search', ['q' => 'Picker']))
        ->assertOk()
        ->assertJsonCount(1, 'patients')
        ->assertJsonPath('patients.0.id', $patient->id);
});

test('a staff-created dentist can be selected when booking an appointment', function () {
    Carbon::setTestNow(Carbon::parse('2026-09-02 09:00:00', 'Africa/Mogadishu'));
    $this->seed(WorkingHourSeeder::class);

    $admin = User::factory()->admin()->create();
    $patient = Patient::factory()->create();
    $chair = Chair::factory()->create();

    $this->actingAs($admin)
        ->post(route('staff.store'), pickerStaffDentistPayload())
        ->assertRedirectToRoute('staff.index');

    $dentist = User::query()->where('email', 'a.yusuf@goldensmile.clinic')->first()?->dentist;

    $this->actingAs($admin)
        ->post(route('appointments.store'), [
            'patient_id' => $patient->id,
            'dentist_id' => $dentist->id,
            'chair_id' => $chair->id,
            'starts_at' => '2026-09-02T09:00',
            'duration_minutes' => 30,
        ])
        ->assertRedirect();

    expect($patient->appointments()->first()?->dentist_id)->toBe($dentist->id);

    Carbon::setTestNow();
});

test('a created supplier and inventory item appear on purchase order create', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('inventory.suppliers.store'), [
            'name' => 'Mogadishu Dental Supply',
        ])
        ->assertRedirect(route('inventory.suppliers.index'));

    $this->actingAs($admin)
        ->post(route('inventory.store'), [
            'name' => 'Picker Composite',
            'category' => InventoryCategory::DentalMaterials->value,
            'quantity' => 4,
            'unit' => 'box',
            'reorder_level' => 1,
            'unit_cost' => '10.00',
            'expiry_date' => now()->addMonths(6)->toDateString(),
        ])
        ->assertRedirect(route('inventory.index'));

    $supplier = Supplier::query()->where('name', 'Mogadishu Dental Supply')->first();
    $item = InventoryItem::query()->where('name', 'Picker Composite')->first();

    expect($supplier)->not->toBeNull();
    expect($item)->not->toBeNull();

    $this->actingAs($admin)
        ->get(route('inventory.purchase-orders.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('inventory/purchase-orders/Create')
            ->has('suppliers', 1)
            ->where('suppliers.0.id', $supplier->id)
            ->where('suppliers.0.label', 'Mogadishu Dental Supply')
            ->has('inventoryItems', 1)
            ->where('inventoryItems.0.id', $item->id)
            ->where('inventoryItems.0.label', 'Picker Composite'));
});
