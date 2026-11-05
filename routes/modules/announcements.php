<?php

use App\Modules\Announcements\Controllers\AnnouncementController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::get('/announcements', [AnnouncementController::class, 'index'])->name('announcements.index');
    Route::post('/announcements', [AnnouncementController::class, 'store'])->name('announcements.store');
    Route::get('/announcements/{id}', [AnnouncementController::class, 'show'])->name('announcements.show');
    Route::put('/announcements/{id}', [AnnouncementController::class, 'update'])->name('announcements.update');
    Route::delete('/announcements/{id}', [AnnouncementController::class, 'destroy'])->name('announcements.destroy');
    Route::post('/announcements/{id}/toggle-status', [AnnouncementController::class, 'toggleStatus'])->name('announcements.toggle-status');
    Route::get('/announcements/{id}/download', [AnnouncementController::class, 'downloadAttachment'])->name('announcements.download');

    // AJAX API endpoints for topbar notifications and modal viewer
    Route::post('/announcements/{id}/read', [AnnouncementController::class, 'markAsRead'])->name('announcements.mark-read');
    Route::post('/announcements/mark-all-read', [AnnouncementController::class, 'markAllAsRead'])->name('announcements.mark-all-read');
    Route::get('/announcements/{id}/detail', [AnnouncementController::class, 'detailJson'])->name('announcements.detail');
    Route::get('/announcements/{id}/readers', [AnnouncementController::class, 'readersJson'])->name('announcements.readers');
});

