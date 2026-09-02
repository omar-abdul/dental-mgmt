<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PlaceholderModuleController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
})->name('home');

Route::middleware('auth')->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::get('patients', [PlaceholderModuleController::class, 'patients'])->name('patients.index');
    Route::get('appointments', [PlaceholderModuleController::class, 'appointments'])->name('appointments.index');
    Route::get('treatments', [PlaceholderModuleController::class, 'treatments'])->name('treatments.index');
    Route::get('billing', [PlaceholderModuleController::class, 'billing'])->name('billing.index');
    Route::get('inventory', [PlaceholderModuleController::class, 'inventory'])->name('inventory.index');
    Route::get('reports', [PlaceholderModuleController::class, 'reports'])->name('reports.index');
});

require __DIR__.'/settings.php';
