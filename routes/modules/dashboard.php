<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Dashboard\Controllers\ProjectDashboardController;
use App\Modules\Dashboard\Controllers\DashboardFilterController;

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [ProjectDashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/modal-body/{modalId}', [ProjectDashboardController::class, 'modalBody'])->name('dashboard.modal-body');
    Route::get('/dashboard/filters', [DashboardFilterController::class, 'getFilterOptions'])->name('dashboard.filters');
    Route::get('/dashboard/filters/{type}', [DashboardFilterController::class, 'getFilterOptionsByType'])->name('dashboard.filters.by-type');
});
