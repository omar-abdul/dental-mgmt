<?php

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\ChartController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EncounterController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\ImagingOrderController;
use App\Http\Controllers\InsuranceClaimController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\LabOrderController;
use App\Http\Controllers\PatientChartController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\PaymentPlanController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\TreatmentController;
use App\Http\Controllers\TreatmentPlanController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
})->name('home');

Route::middleware('auth')->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::get('patients/search', [PatientController::class, 'search'])->name('patients.search');
    Route::resource('patients', PatientController::class)->except(['destroy']);
    Route::post('patients/{patient}/archive', [PatientController::class, 'archive'])->name('patients.archive');
    Route::get('appointments', [AppointmentController::class, 'index'])->name('appointments.index');
    Route::post('appointments', [AppointmentController::class, 'store'])->name('appointments.store');
    Route::put('appointments/{appointment}', [AppointmentController::class, 'update'])->name('appointments.update');
    Route::post('appointments/{appointment}/cancel', [AppointmentController::class, 'cancel'])->name('appointments.cancel');
    Route::post('appointments/{appointment}/check-in', [AppointmentController::class, 'checkIn'])->name('appointments.check-in');
    Route::get('treatments', [TreatmentController::class, 'index'])->name('treatments.index');
    Route::get('treatments/create', [TreatmentController::class, 'create'])->name('treatments.create');
    Route::post('treatments', [TreatmentController::class, 'store'])->name('treatments.store');
    Route::get('treatments/{treatment}', [TreatmentController::class, 'show'])->name('treatments.show');
    Route::post('treatments/{treatment}/complete', [TreatmentController::class, 'complete'])->name('treatments.complete');
    Route::get('chart', [ChartController::class, 'index'])->name('chart.index');
    Route::get('patients/{patient}/chart', [PatientChartController::class, 'show'])->name('patients.chart');
    Route::patch('patients/{patient}/odontogram', [PatientChartController::class, 'updateOdontogram'])->name('patients.odontogram.update');
    Route::post('patients/{patient}/chart/plans', [PatientChartController::class, 'storePlan'])->name('patients.chart.plans.store');
    Route::get('encounters/{encounter}', [EncounterController::class, 'show'])->name('encounters.show');
    Route::patch('encounters/{encounter}/soap', [EncounterController::class, 'updateSoap'])->name('encounters.soap.update');
    Route::post('encounters/{encounter}/sign', [EncounterController::class, 'sign'])->name('encounters.sign');
    Route::post('encounters/{encounter}/amendments', [EncounterController::class, 'storeAmendment'])->name('encounters.amendments.store');
    Route::post('treatment-plans/{treatmentPlan}/items', [TreatmentPlanController::class, 'storeItem'])->name('treatment-plans.items.store');
    Route::patch('treatment-plans/{treatmentPlan}/items/{treatmentPlanItem}', [TreatmentPlanController::class, 'updateItem'])->name('treatment-plans.items.update');
    Route::get('billing', [BillingController::class, 'index'])->name('billing.index');
    Route::get('billing/receipts/{receipt}', [BillingController::class, 'showReceipt'])->name('billing.receipts.show');
    Route::get('billing/{invoice}', [BillingController::class, 'show'])->name('billing.show');
    Route::post('treatments/{treatment}/invoice', [BillingController::class, 'generateFromTreatment'])->name('billing.invoices.generate');
    Route::post('billing/{invoice}/payments', [BillingController::class, 'pay'])->name('billing.payments.store');
    Route::post('billing/{invoice}/refunds', [BillingController::class, 'refund'])->name('billing.refunds.store');
    Route::post('billing/{invoice}/payment-plans', [PaymentPlanController::class, 'store'])->name('billing.payment-plans.store');
    Route::post('billing/{invoice}/insurance-claims', [InsuranceClaimController::class, 'store'])->name('billing.insurance-claims.store');
    Route::get('expenses', [ExpenseController::class, 'index'])->name('expenses.index');
    Route::post('expenses', [ExpenseController::class, 'store'])->name('expenses.store');
    Route::post('expenses/daily-closings', [ExpenseController::class, 'storeDailyClosing'])->name('expenses.daily-closings.store');
    Route::post('expenses/mobile-money-reconciliations', [ExpenseController::class, 'storeMobileMoneyReconciliation'])->name('expenses.mobile-money-reconciliations.store');
    Route::get('lab', [LabOrderController::class, 'index'])->name('lab.index');
    Route::get('lab/create', [LabOrderController::class, 'create'])->name('lab.create');
    Route::post('lab', [LabOrderController::class, 'store'])->name('lab.store');
    Route::get('lab/{labOrder}', [LabOrderController::class, 'show'])->name('lab.show');
    Route::post('lab/{labOrder}/transition', [LabOrderController::class, 'transition'])->name('lab.transition');
    Route::get('imaging', [ImagingOrderController::class, 'index'])->name('imaging.index');
    Route::get('imaging/create', [ImagingOrderController::class, 'create'])->name('imaging.create');
    Route::post('imaging', [ImagingOrderController::class, 'store'])->name('imaging.store');
    Route::get('imaging/{imagingOrder}', [ImagingOrderController::class, 'show'])->name('imaging.show');
    Route::get('inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::post('inventory', [InventoryController::class, 'store'])->name('inventory.store');
    Route::post('inventory/{inventoryItem}/adjust', [InventoryController::class, 'adjust'])->name('inventory.adjust');
    Route::get('inventory/suppliers', [SupplierController::class, 'index'])->name('inventory.suppliers.index');
    Route::post('inventory/suppliers', [SupplierController::class, 'store'])->name('inventory.suppliers.store');
    Route::get('inventory/purchase-orders', [PurchaseOrderController::class, 'index'])->name('inventory.purchase-orders.index');
    Route::get('inventory/purchase-orders/create', [PurchaseOrderController::class, 'create'])->name('inventory.purchase-orders.create');
    Route::post('inventory/purchase-orders', [PurchaseOrderController::class, 'store'])->name('inventory.purchase-orders.store');
    Route::get('inventory/purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'show'])->name('inventory.purchase-orders.show');
    Route::post('inventory/purchase-orders/{purchaseOrder}/receive', [PurchaseOrderController::class, 'receive'])->name('inventory.purchase-orders.receive');
    Route::get('reports', [ReportsController::class, 'index'])->name('reports.index');
    Route::get('reports/daily-appointments', [ReportsController::class, 'dailyAppointments'])->name('reports.daily-appointments');
    Route::get('reports/patient-registration', [ReportsController::class, 'patientRegistration'])->name('reports.patient-registration');
    Route::get('reports/outstanding-balances', [ReportsController::class, 'outstandingBalances'])->name('reports.outstanding-balances');
    Route::get('reports/payments', [ReportsController::class, 'payments'])->name('reports.payments');
    Route::get('reports/inventory-stock', [ReportsController::class, 'inventoryStock'])->name('reports.inventory-stock');
    Route::get('reports/low-stock', [ReportsController::class, 'lowStock'])->name('reports.low-stock');
    Route::get('reports/treatment-statistics', [ReportsController::class, 'treatmentStatistics'])->name('reports.treatment-statistics');
});

require __DIR__.'/settings.php';
