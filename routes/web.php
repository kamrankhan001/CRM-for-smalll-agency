<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\ClientController;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');


Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('leads', LeadController::class);

    Route::resource('clients', ClientController::class);

    Route::resource('users', UserController::class);
    
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
