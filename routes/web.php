<?php

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\PlaceholderModuleController;
use App\Http\Controllers\TreatmentController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
})->name('home');

Route::middleware('auth')->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

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
    Route::get('billing', [PlaceholderModuleController::class, 'billing'])->name('billing.index');
    Route::get('inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::post('inventory', [InventoryController::class, 'store'])->name('inventory.store');
    Route::post('inventory/{inventoryItem}/adjust', [InventoryController::class, 'adjust'])->name('inventory.adjust');
    Route::get('reports', [PlaceholderModuleController::class, 'reports'])->name('reports.index');
});

require __DIR__.'/settings.php';
