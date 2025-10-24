<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use Livewire\Volt\Volt;

use App\Http\Controllers\XeroAuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ConfigureController;
use App\Http\Controllers\WoocommerceController;

Route::get('/', function () {
    return view('welcome');
})->name('home');


Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('profile.edit');
    Volt::route('settings/password', 'settings.password')->name('user-password.edit');
    Volt::route('settings/appearance', 'settings.appearance')->name('appearance.edit');

    Volt::route('settings/two-factor', 'settings.two-factor')
        ->middleware(
            when(
                Features::canManageTwoFactorAuthentication()
                    && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword'),
                ['password.confirm'],
                [],
            ),
        )
        ->name('two-factor.show');

    # Xero
    Route::get('/xero/authorize', [XeroAuthController::class, 'authorizeXero'])
        ->name('xero.authorize');
    Route::post('/xero/disconnect', [XeroAuthController::class, 'handleDisconnect'])
        ->name('xero.disconnect');
    Route::get('/xero/callback', [XeroAuthController::class, 'handleCallback'])
        ->name('xero.callback');


    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    Route::get('/configure', [ConfigureController::class, 'index'])
        ->name('configure');

    Route::get('/admin', [DashboardController::class, 'index'])
        ->name('admin');

    # WooCommerce
    Route::post('/wc/configure', [WoocommerceController::class, 'configureWoocommerce'])
        ->name('wc.configure');
    Route::post('/wc/sync', [WoocommerceController::class, 'syncOrders'])
        ->name('wc.sync');
    Route::post('/wc/map-payments', [WoocommerceController::class, 'savePaymentMapping'])
        ->name('wc.map.payments');
});
