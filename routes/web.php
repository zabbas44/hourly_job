<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\WorkerController;
use App\Http\Controllers\WorkerScheduleController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest.scheduler')->group(function (): void {
    Route::get('/', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->name('login.store');
    Route::get('/auto-login/{token}', [AuthController::class, 'autoLogin'])->name('login.auto');
});

Route::middleware('scheduler.auth')->group(function (): void {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
    Route::post('/payments', [PaymentController::class, 'store'])->name('payments.store');
    Route::get('/payments/export/csv', [PaymentController::class, 'exportCsv'])->name('payments.export.csv');
    Route::get('/payments/{worker}/print', [PaymentController::class, 'print'])->name('payments.print');
    Route::resource('projects', ProjectController::class)->except(['show']);
    Route::resource('workers', WorkerController::class)->except(['show']);
    Route::get('/workers/{worker}/schedule', [WorkerScheduleController::class, 'show'])->name('workers.schedule.show');
    Route::post('/workers/{worker}/schedule', [WorkerScheduleController::class, 'store'])->name('workers.schedule.store');
    Route::delete('/workers/{worker}/schedule/{timeEntry}', [WorkerScheduleController::class, 'destroy'])->name('workers.schedule.destroy');
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
});
