<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Dashboard\Controllers\ProjectDashboardController;

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [ProjectDashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/modal-body/{modalId}', [ProjectDashboardController::class, 'modalBody'])->name('dashboard.modal-body');
});