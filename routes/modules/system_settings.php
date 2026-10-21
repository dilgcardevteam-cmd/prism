<?php

use App\Modules\SystemSettings\Controllers\DatabaseUtilityController;
use Illuminate\Support\Facades\Route;

Route::middleware('superadmin')->group(function () {
    Route::get('/utilities/role-configuration', [DatabaseUtilityController::class, 'roleConfiguration'])
        ->name('utilities.role-configuration.index');
    Route::post('/utilities/role-configuration/role-definitions', [DatabaseUtilityController::class, 'storeRoleDefinition'])
        ->name('utilities.role-configuration.role-definitions.store');
    Route::put('/utilities/role-configuration/role-definitions/{role}', [DatabaseUtilityController::class, 'updateRoleDefinition'])
        ->name('utilities.role-configuration.role-definitions.update');
    Route::delete('/utilities/role-configuration/role-definitions/{role}', [DatabaseUtilityController::class, 'destroyRoleDefinition'])
        ->name('utilities.role-configuration.role-definitions.destroy');
    Route::put('/utilities/role-configuration/roles/{role}', [DatabaseUtilityController::class, 'updateRoleConfiguration'])
        ->name('utilities.role-configuration.roles.update');
    Route::delete('/utilities/role-configuration/roles/{role}', [DatabaseUtilityController::class, 'resetRoleConfiguration'])
        ->name('utilities.role-configuration.roles.reset');
});

Route::prefix('utilities')->name('utilities.')->group(function () {
    Route::get('/system-setup', [DatabaseUtilityController::class, 'systemSetup'])
        ->middleware('crud_permission:utilities_system_setup,view')
        ->name('system-setup.index');
    Route::get('/modules-configuration', [DatabaseUtilityController::class, 'modulesConfiguration'])
        ->middleware('superadmin')
        ->name('modules-configuration.index');
    Route::post('/modules-configuration', [DatabaseUtilityController::class, 'updateModulesConfiguration'])
        ->middleware('superadmin')
        ->name('modules-configuration.update');
    Route::get('/system-maintenance', [DatabaseUtilityController::class, 'systemMaintenance'])
        ->middleware('superadmin')
        ->name('system-maintenance.index');
    Route::post('/system-maintenance/toggle', [DatabaseUtilityController::class, 'toggleSystemMaintenance'])
        ->middleware('superadmin')
        ->name('system-maintenance.toggle');
    Route::get('/notifications', [DatabaseUtilityController::class, 'notifications'])
        ->middleware('crud_permission:utilities_bulk_notifications,view')
        ->name('notifications.index');
    Route::post('/notifications', [DatabaseUtilityController::class, 'sendBulkNotification'])
        ->middleware('crud_permission:utilities_bulk_notifications,add')
        ->name('notifications.broadcast');
    Route::get('/deadlines-configuration', [DatabaseUtilityController::class, 'deadlinesConfiguration'])
        ->middleware('crud_permission:utilities_deadlines_configuration,view')
        ->name('deadlines-configuration.index');
    Route::get('/deadlines-configuration/lgu-reportorial-requirements', [DatabaseUtilityController::class, 'lguReportorialRequirements'])
        ->middleware('crud_permission:utilities_deadlines_configuration,view')
        ->name('deadlines-configuration.lgu-reportorial');
    Route::post('/deadlines-configuration/lgu-reportorial-requirements', [DatabaseUtilityController::class, 'storeLguReportorialDeadline'])
        ->middleware('crud_permission:utilities_deadlines_configuration,update')
        ->name('deadlines-configuration.lgu-reportorial.store');
    Route::get('/deadlines-configuration/dilg-reportorial-requirements', [DatabaseUtilityController::class, 'dilgReportorialRequirements'])
        ->middleware('crud_permission:utilities_deadlines_configuration,view')
        ->name('deadlines-configuration.dilg-reportorial');
    Route::get('/location-configuration', [DatabaseUtilityController::class, 'locationConfiguration'])
        ->middleware('crud_permission:utilities_location_configuration,view')
        ->name('location-configuration.index');
    Route::post('/location-configuration/import/{dataset}', [DatabaseUtilityController::class, 'importLocationDataset'])
        ->middleware('crud_permission:utilities_location_configuration,add')
        ->whereIn('dataset', ['regions', 'provinces', 'city-municipalities'])
        ->name('location-configuration.import');
    Route::post('/location-configuration/import/{dataset}/{importId}/load', [DatabaseUtilityController::class, 'loadLocationDatasetImport'])
        ->middleware('crud_permission:utilities_location_configuration,update')
        ->whereIn('dataset', ['regions', 'provinces', 'city-municipalities'])
        ->whereNumber('importId')
        ->name('location-configuration.load');
    Route::get('/location-configuration/import/{dataset}/{importId}/download', [DatabaseUtilityController::class, 'downloadLocationDatasetImport'])
        ->middleware('crud_permission:utilities_location_configuration,view')
        ->whereIn('dataset', ['regions', 'provinces', 'city-municipalities'])
        ->whereNumber('importId')
        ->name('location-configuration.download');
    Route::delete('/location-configuration/import/{dataset}/{importId}', [DatabaseUtilityController::class, 'deleteLocationDatasetImport'])
        ->middleware('crud_permission:utilities_location_configuration,delete')
        ->whereIn('dataset', ['regions', 'provinces', 'city-municipalities'])
        ->whereNumber('importId')
        ->name('location-configuration.delete');
    Route::get('/backup-and-restore', [DatabaseUtilityController::class, 'index'])
        ->middleware('crud_permission:utilities_backup_restore,view')
        ->name('backup-and-restore.index');
    Route::get('/backup-and-restore/download', [DatabaseUtilityController::class, 'downloadBackup'])
        ->middleware('crud_permission:utilities_backup_restore,view')
        ->name('backup-and-restore.download');
    Route::post('/backup-and-restore/restore', [DatabaseUtilityController::class, 'restore'])
        ->middleware('crud_permission:utilities_backup_restore,update')
        ->name('backup-and-restore.restore');
    Route::post('/backup-and-restore/schedule', [DatabaseUtilityController::class, 'saveSchedule'])
        ->middleware('crud_permission:utilities_backup_restore,update')
        ->name('backup-and-restore.schedule');
    Route::post('/backup-and-restore/test-now', [DatabaseUtilityController::class, 'sendTestBackupNow'])
        ->middleware('crud_permission:utilities_backup_restore,update')
        ->name('backup-and-restore.test-now');
});

