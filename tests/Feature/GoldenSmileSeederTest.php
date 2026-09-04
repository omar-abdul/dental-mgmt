<?php

use App\Enums\ClinicRole;
use App\Models\ActivityLog;
use App\Models\Appointment;
use App\Models\Dentist;
use App\Models\FeeItem;
use App\Models\InventoryItem;
use App\Models\Patient;
use App\Models\User;
use Database\Seeders\FeeItemSeeder;
use Database\Seeders\GoldenSmile\GoldenSmileFixture;
use Database\Seeders\GoldenSmileNamedSeeder;
use Database\Seeders\WorkingHourSeeder;
use Illuminate\Support\Facades\Hash;

beforeEach(function (): void {
    GoldenSmileFixture::reset();
});

test('golden smile named seeder creates demo staff patients skus and activity', function () {
    $this->seed([
        FeeItemSeeder::class,
        WorkingHourSeeder::class,
        GoldenSmileNamedSeeder::class,
    ]);

    expect(User::query()->count())->toBe(7);

    $this->assertDatabaseHas('users', [
        'email' => 'a.santos@goldensmile.clinic',
        'name' => 'Dr. A. Santos',
        'role' => ClinicRole::Admin->value,
    ]);

    $this->assertDatabaseHas('patients', [
        'patient_number' => 'PAT-2026-00001',
        'first_name' => 'Ahmed',
        'last_name' => 'Ali',
    ]);

    $this->assertDatabaseHas('patients', [
        'first_name' => 'Maria',
        'last_name' => 'Santos',
    ]);

    expect(Dentist::query()->count())->toBe(3);
    expect(Appointment::query()->count())->toBe(9);
    expect(ActivityLog::query()->count())->toBe(5);

    $this->assertDatabaseHas('inventory_items', ['name' => 'Disposable Gloves (Box)']);
    $this->assertDatabaseHas('inventory_items', ['name' => 'Composite Resin (Syringe)']);
    $this->assertDatabaseHas('inventory_items', ['name' => 'Local Anesthetic Cartridge']);

    foreach (GoldenSmileFixture::feeDcmsIds() as $dcmsId) {
        $code = collect(GoldenSmileFixture::data()['fee_items'])
            ->firstWhere('dcms_id', $dcmsId)['code'];

        expect(FeeItem::query()->where('code', $code)->exists())->toBeTrue("Missing fee catalog code [{$code}] for {$dcmsId}");
    }
});

test('each clinic role can authenticate with the demo password', function (ClinicRole $role, string $email) {
    $this->seed([
        FeeItemSeeder::class,
        WorkingHourSeeder::class,
        GoldenSmileNamedSeeder::class,
    ]);

    $user = User::query()->where('email', $email)->firstOrFail();

    expect($user->role)->toBe($role);
    expect(Hash::check('password12', $user->password))->toBeTrue();

    $response = $this->post('/login', [
        'email' => $email,
        'password' => 'password12',
    ]);

    $response->assertRedirect(route('dashboard', absolute: false));
    $this->assertAuthenticatedAs($user);
})->with([
    'admin' => [ClinicRole::Admin, 'a.santos@goldensmile.clinic'],
    'dentist' => [ClinicRole::Dentist, 'r.lim@goldensmile.clinic'],
    'receptionist' => [ClinicRole::Receptionist, 'receptionist@goldensmile.clinic'],
    'nurse' => [ClinicRole::Nurse, 'nurse@goldensmile.clinic'],
    'accountant' => [ClinicRole::Accountant, 'accountant@goldensmile.clinic'],
    'lab' => [ClinicRole::Lab, 'lab@goldensmile.clinic'],
]);

test('golden smile generate plan matches fixture arithmetic without inserting the full patient set', function () {
    expect(GoldenSmileFixture::extraActivePatientsCount())
        ->toBe(GoldenSmileFixture::kpis()['active_patients'] - GoldenSmileFixture::namedActivePatientCount());

    expect(GoldenSmileFixture::extraTodaysAppointmentsCount())
        ->toBe(GoldenSmileFixture::kpis()['todays_appointments'] - GoldenSmileFixture::namedTodayAppointmentCount());

    expect(GoldenSmileFixture::extraPendingInvoicesCount())
        ->toBe(GoldenSmileFixture::kpis()['pending_invoices'] - GoldenSmileFixture::namedPendingInvoiceCount());

    expect(GoldenSmileFixture::extraInventoryItemCount())
        ->toBe(GoldenSmileFixture::kpis()['inventory_item_count'] - GoldenSmileFixture::namedInventoryItemCount());

    expect(GoldenSmileFixture::extraLowStockItemCount())
        ->toBe(GoldenSmileFixture::kpis()['low_stock_items'] - GoldenSmileFixture::namedLowStockItemCount());

    expect(GoldenSmileFixture::extraStockValueCents())
        ->toBe(GoldenSmileFixture::kpis()['stock_value_cents'] - GoldenSmileFixture::namedStockValueCents());

    expect(GoldenSmileFixture::extraActivePatientsCount())->toBe(1275);
    expect(GoldenSmileFixture::extraTodaysAppointmentsCount())->toBe(9);
    expect(GoldenSmileFixture::extraPendingInvoicesCount())->toBe(7);
    expect(GoldenSmileFixture::extraInventoryItemCount())->toBe(83);
    expect(GoldenSmileFixture::extraLowStockItemCount())->toBe(2);
});

test('named seeder alone does not create the generated patient volume', function () {
    $this->seed([
        FeeItemSeeder::class,
        WorkingHourSeeder::class,
        GoldenSmileNamedSeeder::class,
    ]);

    expect(Patient::query()->count())->toBe(9);
    expect(InventoryItem::query()->count())->toBe(3);
});
