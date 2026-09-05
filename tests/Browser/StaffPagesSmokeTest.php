<?php

use App\Models\Patient;
use App\Models\User;
use Tests\Support\ClinicSurface;

test('guests can open public auth pages', function (Closure $visit) {
    $visit();
})->with([
    'login' => [function () {
        visit(route('login'))
            ->assertSee('Username or Email')
            ->assertNoJavaScriptErrors();
    }],
    'forgot password' => [function () {
        visit(route('password.request'))
            ->assertSee('Email address')
            ->assertNoJavaScriptErrors();
    }],
    'reset password' => [function () {
        visit(route('password.reset', ['token' => 'coverage-token']))
            ->assertSee('Email')
            ->assertNoJavaScriptErrors();
    }],
]);

test('staff can open {page}', function (Closure $visit) {
    $admin = User::factory()->admin()->create(['name' => 'Coverage Admin']);

    $this->actingAs($admin);

    $visit();
})->with([
    'confirm password' => [function () {
        visit(route('password.confirm'))
            ->assertSee('Password')
            ->assertNoJavaScriptErrors();
    }],
    'dashboard' => [function () {
        visit(route('dashboard'))
            ->assertSee('Overview')
            ->assertNoJavaScriptErrors();
    }],
    'patients index' => [function () {
        visit(route('patients.index'))
            ->assertSee('Patients')
            ->assertNoJavaScriptErrors();
    }],
    'patients create' => [function () {
        visit(route('patients.create'))
            ->assertSee('Register patient')
            ->assertNoJavaScriptErrors();
    }],
    'patients show' => [function () {
        $patient = ClinicSurface::records()['patient'];
        visit(route('patients.show', $patient))
            ->assertSee('Visible Patient')
            ->assertNoJavaScriptErrors();
    }],
    'patients edit' => [function () {
        $patient = ClinicSurface::records()['patient'];
        visit(route('patients.edit', $patient))
            ->assertSee('Edit Visible Patient')
            ->assertNoJavaScriptErrors();
    }],
    'appointments' => [function () {
        visit(route('appointments.index'))
            ->assertSee('Appointments')
            ->assertNoJavaScriptErrors();
    }],
    'treatments index' => [function () {
        visit(route('treatments.index'))
            ->assertSee('Treatments')
            ->assertNoJavaScriptErrors();
    }],
    'treatments create' => [function () {
        visit(route('treatments.create'))
            ->assertSee('Record treatment')
            ->assertNoJavaScriptErrors();
    }],
    'treatments show' => [function () {
        $treatment = ClinicSurface::records()['treatment'];
        visit(route('treatments.show', $treatment))
            ->assertSee('Coverage diagnosis')
            ->assertNoJavaScriptErrors();
    }],
    'chart index' => [function () {
        visit(route('chart.index'))
            ->assertSee('Clinical chart')
            ->assertNoJavaScriptErrors();
    }],
    'patient chart read-only' => [function () {
        $nurse = User::factory()->nurse()->create();
        $patient = Patient::factory()->create([
            'first_name' => 'Visible',
            'last_name' => 'Patient',
        ]);

        test()->actingAs($nurse);

        visit(route('patients.chart', $patient))
            ->assertSee('Patient chart')
            ->assertSee('Visible Patient')
            ->assertSee('You have read-only access to the odontogram.')
            ->assertNoJavaScriptErrors();
    }],
    'patient chart writable' => [function () {
        $patient = ClinicSurface::records()['patient'];

        visit(route('patients.chart', $patient))
            ->assertSee('Visible Patient')
            ->assertSee('Save tooth')
            ->assertSee('Update status')
            ->assertNoJavaScriptErrors();
    }],
    'encounter show' => [function () {
        $encounter = ClinicSurface::records()['encounter'];
        visit(route('encounters.show', $encounter))
            ->assertSee($encounter->number)
            ->assertNoJavaScriptErrors();
    }],
    'billing index' => [function () {
        visit(route('billing.index'))
            ->assertSee('Billing')
            ->assertNoJavaScriptErrors();
    }],
    'billing show' => [function () {
        $invoice = ClinicSurface::records()['invoice'];
        visit(route('billing.show', $invoice))
            ->assertSee($invoice->invoice_number)
            ->assertNoJavaScriptErrors();
    }],
    'receipt show' => [function () {
        $receipt = ClinicSurface::records()['receipt'];
        visit(route('billing.receipts.show', $receipt))
            ->assertSee('Payment Receipt')
            ->assertSee($receipt->receipt_number)
            ->assertNoJavaScriptErrors();
    }],
    'expenses' => [function () {
        visit(route('expenses.index'))
            ->assertSee('Expenses')
            ->assertNoJavaScriptErrors();
    }],
    'lab index' => [function () {
        visit(route('lab.index'))
            ->assertSee('Lab orders')
            ->assertNoJavaScriptErrors();
    }],
    'lab create' => [function () {
        visit(route('lab.create'))
            ->assertSee('New lab order')
            ->assertNoJavaScriptErrors();
    }],
    'lab show' => [function () {
        $order = ClinicSurface::records()['labOrder'];
        visit(route('lab.show', $order))
            ->assertSee($order->number)
            ->assertNoJavaScriptErrors();
    }],
    'imaging index' => [function () {
        visit(route('imaging.index'))
            ->assertSee('Imaging orders')
            ->assertNoJavaScriptErrors();
    }],
    'imaging create' => [function () {
        visit(route('imaging.create'))
            ->assertSee('New imaging order')
            ->assertNoJavaScriptErrors();
    }],
    'imaging show' => [function () {
        $order = ClinicSurface::records()['imagingOrder'];
        visit(route('imaging.show', $order))
            ->assertSee($order->number)
            ->assertNoJavaScriptErrors();
    }],
    'inventory' => [function () {
        visit(route('inventory.index'))
            ->assertSee('Inventory')
            ->assertNoJavaScriptErrors();
    }],
    'suppliers' => [function () {
        visit(route('inventory.suppliers.index'))
            ->assertSee('Suppliers')
            ->assertNoJavaScriptErrors();
    }],
    'purchase orders index' => [function () {
        visit(route('inventory.purchase-orders.index'))
            ->assertSee('Purchase orders')
            ->assertNoJavaScriptErrors();
    }],
    'purchase orders create' => [function () {
        visit(route('inventory.purchase-orders.create'))
            ->assertSee('New purchase order')
            ->assertNoJavaScriptErrors();
    }],
    'purchase order show' => [function () {
        $order = ClinicSurface::records()['purchaseOrder'];
        visit(route('inventory.purchase-orders.show', $order))
            ->assertSee($order->number)
            ->assertNoJavaScriptErrors();
    }],
    'reports hub' => [function () {
        visit(route('reports.index'))
            ->assertSee('Reports')
            ->assertNoJavaScriptErrors();
    }],
    'reports daily appointments' => [function () {
        visit(route('reports.daily-appointments'))
            ->assertSee('Daily appointments')
            ->assertNoJavaScriptErrors();
    }],
    'reports patient registration' => [function () {
        visit(route('reports.patient-registration'))
            ->assertSee('Patient registration')
            ->assertNoJavaScriptErrors();
    }],
    'reports outstanding' => [function () {
        visit(route('reports.outstanding-balances'))
            ->assertSee('Outstanding balances')
            ->assertNoJavaScriptErrors();
    }],
    'reports payments' => [function () {
        visit(route('reports.payments'))
            ->assertSee('Payments')
            ->assertNoJavaScriptErrors();
    }],
    'reports inventory stock' => [function () {
        visit(route('reports.inventory-stock'))
            ->assertSee('Inventory stock')
            ->assertNoJavaScriptErrors();
    }],
    'reports low stock' => [function () {
        visit(route('reports.low-stock'))
            ->assertSee('Low stock')
            ->assertNoJavaScriptErrors();
    }],
    'reports treatment statistics' => [function () {
        visit(route('reports.treatment-statistics'))
            ->assertSee('Treatment statistics')
            ->assertNoJavaScriptErrors();
    }],
    'profile' => [function () {
        visit(route('profile.edit'))
            ->assertSee('Profile')
            ->assertNoJavaScriptErrors();
    }],
    'security' => [function () {
        test()->withSession(['auth.password_confirmed_at' => time()]);

        visit(route('security.edit'))
            ->assertSee('Update password')
            ->assertNoJavaScriptErrors();
    }],
    'appearance' => [function () {
        visit(route('appearance.edit'))
            ->assertSee('Appearance settings')
            ->assertNoJavaScriptErrors();
    }],
    'staff' => [function () {
        visit(route('staff.index'))
            ->assertSee('Staff')
            ->assertNoJavaScriptErrors();
    }],
    'staff create' => [function () {
        visit(route('staff.create'))
            ->assertSee('Staff')
            ->assertNoJavaScriptErrors();
    }],
    'notification templates' => [function () {
        visit(route('notification-templates.index'))
            ->assertSee('Notification templates')
            ->assertNoJavaScriptErrors();
    }],
]);
