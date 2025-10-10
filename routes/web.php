<?php

use App\Http\Controllers\ClientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ActivityController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\InvoiceController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/leads/export', [LeadController::class, 'export'])->name('leads.export');
    Route::post('/leads/import', [LeadController::class, 'import'])->name('leads.import');
    Route::get('/leads/import/sample', [LeadController::class, 'downloadSample'])->name('leads.import.sample');

    // Keep the resource route but it should be after custom routes
    Route::resource('leads', LeadController::class);
    Route::post('/leads/{lead}/convert', [LeadController::class, 'convert'])
    ->name('leads.convert');

    Route::resource('clients', ClientController::class);

    Route::resource('tasks', TaskController::class);

    Route::resource('notes', NoteController::class);

    Route::resource('projects', ProjectController::class);

    Route::resource('documents', DocumentController::class);

    Route::resource('activities', ActivityController::class)->only(['index', 'show', 'destroy']);
    
    Route::resource('invoices', InvoiceController::class);

    Route::resource('users', UserController::class);


    Route::get('/notifications', [NotificationController::class, 'index'])
    ->name('notifications.index');
    Route::post('/notifications/{id}/mark-read', [NotificationController::class, 'markAsRead'])->name('notifications.markRead');
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.markAll');


});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
