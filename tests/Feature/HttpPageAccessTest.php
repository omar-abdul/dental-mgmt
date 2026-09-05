<?php

use App\Enums\ClinicRole;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\ClinicSurface;

test('guests are redirected to login for authenticated pages', function (string $route, Closure $params) {
    $this->get(route($route, $params()))
        ->assertRedirectToRoute('login');
})->with([
    'dashboard' => ['dashboard', fn () => []],
    'patients index' => ['patients.index', fn () => []],
    'patients create' => ['patients.create', fn () => []],
    'patients show' => ['patients.show', fn () => ['patient' => ClinicSurface::records()['patient']]],
    'patients edit' => ['patients.edit', fn () => ['patient' => ClinicSurface::records()['patient']]],
    'patients search' => ['patients.search', fn () => []],
    'appointments index' => ['appointments.index', fn () => []],
    'treatments index' => ['treatments.index', fn () => []],
    'treatments create' => ['treatments.create', fn () => []],
    'treatments show' => ['treatments.show', fn () => ['treatment' => ClinicSurface::records()['treatment']]],
    'chart index' => ['chart.index', fn () => []],
    'patient chart' => ['patients.chart', fn () => ['patient' => ClinicSurface::records()['patient']]],
    'encounter show' => ['encounters.show', fn () => ['encounter' => ClinicSurface::records()['encounter']]],
    'billing index' => ['billing.index', fn () => []],
    'billing show' => ['billing.show', fn () => ['invoice' => ClinicSurface::records()['invoice']]],
    'receipt show' => ['billing.receipts.show', fn () => ['receipt' => ClinicSurface::records()['receipt']]],
    'expenses index' => ['expenses.index', fn () => []],
    'lab index' => ['lab.index', fn () => []],
    'lab create' => ['lab.create', fn () => []],
    'lab show' => ['lab.show', fn () => ['labOrder' => ClinicSurface::records()['labOrder']]],
    'imaging index' => ['imaging.index', fn () => []],
    'imaging create' => ['imaging.create', fn () => []],
    'imaging show' => ['imaging.show', fn () => ['imagingOrder' => ClinicSurface::records()['imagingOrder']]],
    'inventory index' => ['inventory.index', fn () => []],
    'suppliers index' => ['inventory.suppliers.index', fn () => []],
    'purchase orders index' => ['inventory.purchase-orders.index', fn () => []],
    'purchase orders create' => ['inventory.purchase-orders.create', fn () => []],
    'purchase order show' => ['inventory.purchase-orders.show', fn () => ['purchaseOrder' => ClinicSurface::records()['purchaseOrder']]],
    'reports hub' => ['reports.index', fn () => []],
    'reports daily appointments' => ['reports.daily-appointments', fn () => []],
    'reports patient registration' => ['reports.patient-registration', fn () => []],
    'reports outstanding' => ['reports.outstanding-balances', fn () => []],
    'reports payments' => ['reports.payments', fn () => []],
    'reports inventory stock' => ['reports.inventory-stock', fn () => []],
    'reports low stock' => ['reports.low-stock', fn () => []],
    'reports treatment statistics' => ['reports.treatment-statistics', fn () => []],
    'profile' => ['profile.edit', fn () => []],
    'security' => ['security.edit', fn () => []],
    'appearance' => ['appearance.edit', fn () => []],
    'staff index' => ['staff.index', fn () => []],
    'staff create' => ['staff.create', fn () => []],
    'notification templates' => ['notification-templates.index', fn () => []],
    'confirm password' => ['password.confirm', fn () => []],
]);

test('guest auth pages render', function (string $route, string $component, Closure $params) {
    $this->get(route($route, $params()))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component($component));
})->with([
    'login' => ['login', 'auth/Login', fn () => []],
    'forgot password' => ['password.request', 'auth/ForgotPassword', fn () => []],
    'reset password' => ['password.reset', 'auth/ResetPassword', fn () => ['token' => 'coverage-token']],
]);

test('admin can open each staff page', function (string $route, string $component, Closure $params) {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route($route, $params($admin)))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component($component));
})->with([
    'dashboard' => ['dashboard', 'Dashboard', fn () => []],
    'patients index' => ['patients.index', 'patients/Index', fn () => []],
    'patients create' => ['patients.create', 'patients/Create', fn () => []],
    'patients show' => ['patients.show', 'patients/Show', fn () => ['patient' => ClinicSurface::records()['patient']]],
    'patients edit' => ['patients.edit', 'patients/Edit', fn () => ['patient' => ClinicSurface::records()['patient']]],
    'appointments index' => ['appointments.index', 'appointments/Index', fn () => []],
    'treatments index' => ['treatments.index', 'treatments/Index', fn () => []],
    'treatments create' => ['treatments.create', 'treatments/Create', fn () => []],
    'treatments show' => ['treatments.show', 'treatments/Show', fn () => ['treatment' => ClinicSurface::records()['treatment']]],
    'chart index' => ['chart.index', 'chart/Index', fn () => []],
    'patient chart' => ['patients.chart', 'chart/PatientChart', fn () => ['patient' => ClinicSurface::records()['patient']]],
    'encounter show' => ['encounters.show', 'encounters/Show', fn () => ['encounter' => ClinicSurface::records()['encounter']]],
    'billing index' => ['billing.index', 'billing/Index', fn () => []],
    'billing show' => ['billing.show', 'billing/Show', fn () => ['invoice' => ClinicSurface::records()['invoice']]],
    'receipt show' => ['billing.receipts.show', 'billing/Receipt', fn () => ['receipt' => ClinicSurface::records()['receipt']]],
    'expenses index' => ['expenses.index', 'expenses/Index', fn () => []],
    'lab index' => ['lab.index', 'lab/Index', fn () => []],
    'lab create' => ['lab.create', 'lab/Create', fn () => []],
    'lab show' => ['lab.show', 'lab/Show', fn () => ['labOrder' => ClinicSurface::records()['labOrder']]],
    'imaging index' => ['imaging.index', 'imaging/Index', fn () => []],
    'imaging create' => ['imaging.create', 'imaging/Create', fn () => []],
    'imaging show' => ['imaging.show', 'imaging/Show', fn () => ['imagingOrder' => ClinicSurface::records()['imagingOrder']]],
    'inventory index' => ['inventory.index', 'inventory/Index', fn () => []],
    'suppliers index' => ['inventory.suppliers.index', 'inventory/suppliers/Index', fn () => []],
    'purchase orders index' => ['inventory.purchase-orders.index', 'inventory/purchase-orders/Index', fn () => []],
    'purchase orders create' => ['inventory.purchase-orders.create', 'inventory/purchase-orders/Create', fn () => []],
    'purchase order show' => ['inventory.purchase-orders.show', 'inventory/purchase-orders/Show', fn () => ['purchaseOrder' => ClinicSurface::records()['purchaseOrder']]],
    'reports hub' => ['reports.index', 'reports/Index', fn () => []],
    'reports daily appointments' => ['reports.daily-appointments', 'reports/DailyAppointments', fn () => []],
    'reports patient registration' => ['reports.patient-registration', 'reports/PatientRegistration', fn () => []],
    'reports outstanding' => ['reports.outstanding-balances', 'reports/OutstandingBalances', fn () => []],
    'reports payments' => ['reports.payments', 'reports/Payments', fn () => []],
    'reports inventory stock' => ['reports.inventory-stock', 'reports/InventoryStock', fn () => []],
    'reports low stock' => ['reports.low-stock', 'reports/LowStock', fn () => []],
    'reports treatment statistics' => ['reports.treatment-statistics', 'reports/TreatmentStatistics', fn () => []],
    'profile' => ['profile.edit', 'settings/Profile', fn () => []],
    'security' => ['security.edit', 'settings/Security', fn () => []],
    'appearance' => ['appearance.edit', 'settings/Appearance', fn () => []],
    'staff index' => ['staff.index', 'settings/Staff', fn () => []],
    'staff create' => ['staff.create', 'settings/Staff', fn () => []],
    'notification templates' => ['notification-templates.index', 'settings/NotificationTemplates', fn () => []],
    'confirm password' => ['password.confirm', 'auth/ConfirmPassword', fn () => []],
]);

test('forbidden roles cannot open restricted pages', function (ClinicRole $role, string $route, Closure $params) {
    $user = User::factory()->role($role)->create();

    $this->actingAs($user)
        ->get(route($route, $params()))
        ->assertForbidden();
})->with([
    'accountant patients' => [ClinicRole::Accountant, 'patients.index', fn () => []],
    'lab patients' => [ClinicRole::Lab, 'patients.create', fn () => []],
    'dentist patient edit' => [ClinicRole::Dentist, 'patients.edit', fn () => ['patient' => ClinicSurface::records()['patient']]],
    'accountant appointments' => [ClinicRole::Accountant, 'appointments.index', fn () => []],
    'lab treatments' => [ClinicRole::Lab, 'treatments.index', fn () => []],
    'receptionist chart' => [ClinicRole::Receptionist, 'chart.index', fn () => []],
    'receptionist patient chart' => [ClinicRole::Receptionist, 'patients.chart', fn () => ['patient' => ClinicSurface::records()['patient']]],
    'lab billing' => [ClinicRole::Lab, 'billing.index', fn () => []],
    'nurse billing' => [ClinicRole::Nurse, 'billing.show', fn () => ['invoice' => ClinicSurface::records()['invoice']]],
    'dentist expenses' => [ClinicRole::Dentist, 'expenses.index', fn () => []],
    'receptionist lab' => [ClinicRole::Receptionist, 'lab.index', fn () => []],
    'receptionist imaging' => [ClinicRole::Receptionist, 'imaging.index', fn () => []],
    'accountant inventory' => [ClinicRole::Accountant, 'inventory.index', fn () => []],
    'lab inventory suppliers' => [ClinicRole::Lab, 'inventory.suppliers.index', fn () => []],
    'dentist finance report' => [ClinicRole::Dentist, 'reports.outstanding-balances', fn () => []],
    'dentist staff' => [ClinicRole::Dentist, 'staff.index', fn () => []],
    'dentist notification templates' => [ClinicRole::Dentist, 'notification-templates.index', fn () => []],
]);

test('patient search returns json for authorized staff', function () {
    $receptionist = User::factory()->receptionist()->create();
    $patient = ClinicSurface::records()['patient'];

    $this->actingAs($receptionist)
        ->getJson(route('patients.search', ['q' => $patient->first_name]))
        ->assertOk()
        ->assertJsonStructure(['patients']);
});
