<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Public Routes
Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

// Authenticated Routes
Route::middleware(['auth', 'verified'])->group(function () {
    
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Leads Routes
    Route::prefix('leads')->group(function () {
        Route::get('/export', [LeadController::class, 'export'])->name('leads.export');
        Route::get('/export/download', [LeadController::class, 'downloadExport'])->name('leads.downloadExport');
        Route::get('/import/sample', [LeadController::class, 'downloadSample'])->name('leads.import.sample');
        Route::post('/import', [LeadController::class, 'import'])->name('leads.import');
        Route::post('/{lead}/convert', [LeadController::class, 'convert'])->name('leads.convert');
        Route::resource('/', LeadController::class)->names('leads')->parameters(['' => 'lead']);
    });

    // Appointments Routes
    Route::prefix('appointments')->group(function () {
        Route::patch('/{appointment}/cancel', [AppointmentController::class, 'cancel'])->name('appointments.cancel');
        Route::resource('/', AppointmentController::class)->names('appointments')->parameters(['' => 'appointment']);
    });

    // Clients Routes
    Route::resource('clients', ClientController::class);

    // Tasks Routes
    Route::prefix('tasks')->group(function () {
        Route::put('/{task}/complete', [TaskController::class, 'complete'])->name('tasks.complete');
        Route::resource('/', TaskController::class)->names('tasks')->parameters(['' => 'task']);
    });

    // Notes Routes
    Route::resource('notes', NoteController::class);

    // Projects Routes
    Route::resource('projects', ProjectController::class);

    // Documents Routes
    Route::prefix('documents')->group(function () {
        Route::get('/{document}/download', [DocumentController::class, 'download'])->name('documents.download');
        Route::resource('/', DocumentController::class)->names('documents')->parameters(['' => 'document']);
    });

    // Activities Routes (Limited)
    Route::resource('activities', ActivityController::class)->only(['index', 'show', 'destroy']);

    // Invoices Routes
    Route::prefix('invoices')->group(function () {
        Route::get('/{invoice}/download', [InvoiceController::class, 'download'])->name('invoices.download');
        Route::post('/{invoice}/send', [InvoiceController::class, 'send'])->name('invoices.send');
        Route::put('/{invoice}/mark-as-paid', [InvoiceController::class, 'markAsPaid'])->name('invoices.mark-as-paid');
        Route::resource('/', InvoiceController::class)->names('invoices')->parameters(['' => 'invoice']);
    });

    // Users Routes
    Route::resource('users', UserController::class);

    // Notifications Routes
    Route::prefix('notifications')->group(function () {
        Route::post('/{id}/mark-read', [NotificationController::class, 'markAsRead'])->name('notifications.markRead');
        Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.markAll');
        Route::get('/', [NotificationController::class, 'index'])->name('notifications.index');
    });

});

// Include additional route files
require __DIR__.'/settings.php';
require __DIR__.'/auth.php';