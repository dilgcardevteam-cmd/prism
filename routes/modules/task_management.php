<?php

use Illuminate\Support\Facades\Route;
use App\Modules\TaskManagement\Controllers\TaskManagementController;

Route::middleware(['auth'])->group(function () {
    Route::get('/approval-management', [TaskManagementController::class, 'index'])
        ->middleware('crud_permission:approval_management,view')
        ->name('task-management.index');

    Route::redirect('/task-management', '/approval-management');
});
