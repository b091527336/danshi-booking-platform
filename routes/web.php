<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\SyncCenterController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/bookings', [BookingController::class, 'index'])->name('bookings.index');
    Route::get('/bookings/{booking}', [BookingController::class, 'show'])->name('bookings.show');

    Route::resource('customers', CustomerController::class)
        ->only(['index', 'show', 'edit', 'update']);

    Route::resource('organizations', OrganizationController::class)
        ->only(['index', 'show', 'edit', 'update']);

    Route::get('/sync', [SyncCenterController::class, 'index'])->name('sync.index');
    Route::post('/sync/{organization}', [SyncCenterController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('sync.store');

    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});
