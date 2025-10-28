<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

use App\Http\Controllers\XeroAuthController;
use App\Http\Controllers\AdminController;

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
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


});

require __DIR__.'/socialstream.php';