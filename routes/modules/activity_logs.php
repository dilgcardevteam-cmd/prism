<?php

use App\Modules\ActivityLogs\Controllers\ActivityLogController;
use Illuminate\Support\Facades\Route;

Route::prefix('utilities')->name('utilities.')->group(function () {
    Route::get('/activity-logs', [ActivityLogController::class, 'index'])
        ->middleware('crud_permission:utilities_activity_logs,view')
        ->name('activity-logs.index');
    Route::get('/activity-logs/export', [ActivityLogController::class, 'export'])
        ->middleware('crud_permission:utilities_activity_logs,view')
        ->name('activity-logs.export');
});

