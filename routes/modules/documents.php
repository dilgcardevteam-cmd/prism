<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Documents\Controllers\NadaiManagementController;

Route::middleware(['auth'])->group(function () {
    Route::get('/nadai-management', [NadaiManagementController::class, 'index'])
        ->name('nadai-management.index');
    Route::get('/nadai-management/{office}', [NadaiManagementController::class, 'show'])
        ->name('nadai-management.show');
    Route::post('/nadai-management/{office}/upload', [NadaiManagementController::class, 'store'])
        ->name('nadai-management.store');
    Route::get('/nadai-management/{office}/document/{docId}', [NadaiManagementController::class, 'viewDocument'])
        ->name('nadai-management.document');
    Route::get('/nadai-management/{office}/document/{docId}/open', [NadaiManagementController::class, 'openDocumentAndRedirect'])
        ->name('nadai-management.open-document');
    Route::get('/nadai-management/{office}/document/{docId}/download', [NadaiManagementController::class, 'downloadDocument'])
        ->name('nadai-management.download-document');
    Route::match(['put', 'patch'], '/nadai-management/{office}/document/{docId}', [NadaiManagementController::class, 'updateDocument'])
        ->name('nadai-management.update-document');
    Route::delete('/nadai-management/{office}/document/{docId}', [NadaiManagementController::class, 'deleteDocument'])
        ->name('nadai-management.delete-document');
});

