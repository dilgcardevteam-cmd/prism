<?php

use Illuminate\Support\Facades\Route;
use App\Modules\TaskManagement\Controllers\TaskManagementController;

Route::middleware(['auth'])->group(function () {
    Route::get('/task-management', [TaskManagementController::class, 'index'])
        ->name('task-management.index');
});

