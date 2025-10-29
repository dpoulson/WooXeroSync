<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

use App\Http\Controllers\XeroAuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\TeamBillingController;
use App\Livewire\BillingSuccess;
use App\Livewire\BillingCancel;

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
    'hasTeam',
])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::get('/configure', function () {
        return view('configure');
    })->name('configure');

    Route::get('/logs', function () {
        return view('logs');
    })->name('logs');

    Route::get('/admin', [AdminController::class, 'index'])
    ->middleware('can:view admin panel') // <-- Spatie middleware check
    ->name('admin');

    # Xero
    Route::get('/xero/authorize', [XeroAuthController::class, 'authorizeXero'])
        ->name('xero.authorize');
    Route::post('/xero/disconnect', [XeroAuthController::class, 'handleDisconnect'])
        ->name('xero.disconnect');
    Route::get('/xero/callback', [XeroAuthController::class, 'handleCallback'])
        ->name('xero.callback');

    // Routes for Team-Based Billing
    Route::get('/team/{team}/subscribe', [TeamBillingController::class, 'checkout'])->name('billing.checkout');
    Route::get('/team/{team}/portal', [TeamBillingController::class, 'portal'])->name('billing.portal');
    // Success and Cancel routes (optional, but good practice)
    Route::get('/billing/success', BillingSuccess::class)->name('billing.success');

    // Route for cancelled checkout (includes team ID from your controller redirect)
    Route::get('/billing/cancel/{team}', BillingCancel::class)->name('billing.cancel');
});

require __DIR__.'/socialstream.php';