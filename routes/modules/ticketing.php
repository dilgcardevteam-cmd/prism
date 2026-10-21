<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\DashboardController;
use App\Modules\Ticketing\Controllers\TicketCommentController;
use App\Modules\Ticketing\Controllers\TicketController;
use App\Modules\Ticketing\Controllers\TicketHistoryController;
use Illuminate\Support\Facades\Route;

Route::prefix('ticketing')->name('ticketing.')->middleware('crud_permission:ticketing_system,view')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/tickets/{ticket}', [TicketController::class, 'show'])->name('show');
    Route::get('/tickets/{ticket}/attachments/{attachment}', [TicketController::class, 'downloadAttachment'])->name('attachments.download');
    Route::get('/tickets/{ticket}/attachments/{attachment}/view', [TicketController::class, 'viewAttachment'])->name('attachments.view');
    Route::get('/tickets/{ticket}/history', [TicketHistoryController::class, 'index'])->name('history.index');
    Route::post('/tickets/{ticket}/comments', [TicketCommentController::class, 'store'])->name('comments.store');
    Route::post('/tickets/{ticket}/pending', [TicketController::class, 'markPending'])->name('pending');
    Route::post('/tickets/{ticket}/resume', [TicketController::class, 'resume'])->name('resume');
    Route::post('/tickets/{ticket}/reopen', [TicketController::class, 'reopen'])->name('reopen');

    Route::middleware('crud_permission:ticketing_system,add')->group(function () {
        Route::get('/submit', [TicketController::class, 'create'])->name('create');
        Route::post('/submit', [TicketController::class, 'store'])->name('store');
        Route::get('/my-tickets', [TicketController::class, 'myTickets'])->name('my-tickets');
        Route::get('/track', [TicketController::class, 'track'])->name('track');
    });

    Route::prefix('province')->name('province.')->middleware('role:province')->group(function () {
        Route::get('/tickets', [TicketController::class, 'provincialIndex'])->name('index');
        Route::post('/tickets/{ticket}/accept', [TicketController::class, 'provinceAccept'])->name('accept');
        Route::post('/tickets/{ticket}/start-review', [TicketController::class, 'provinceStartReview'])->name('start-review');
        Route::post('/tickets/{ticket}/resolve', [TicketController::class, 'provinceResolve'])->name('resolve');
        Route::post('/tickets/{ticket}/escalate', [TicketController::class, 'provinceEscalate'])->name('escalate');
    });

    Route::prefix('region')->name('region.')->middleware('role:region')->group(function () {
        Route::get('/tickets', [TicketController::class, 'regionalIndex'])->name('index');
        Route::post('/tickets/{ticket}/accept', [TicketController::class, 'regionAccept'])->name('accept');
        Route::post('/tickets/{ticket}/start-review', [TicketController::class, 'regionStartReview'])->name('start-review');
        Route::post('/tickets/{ticket}/resolve', [TicketController::class, 'regionResolve'])->name('resolve');
    });

    Route::prefix('admin')->name('admin.')->middleware('role:admin')->group(function () {
        Route::get('/tickets', [AdminController::class, 'index'])->name('index');
        Route::post('/tickets/{ticket}/start-review', [AdminController::class, 'startTicketReview'])->name('start-review');
        Route::post('/tickets/{ticket}/resolve', [AdminController::class, 'resolveAssignedTicket'])->name('resolve');
        Route::post('/categories', [AdminController::class, 'storeCategory'])->name('categories.store');
        Route::put('/categories/{category}', [AdminController::class, 'updateCategory'])->name('categories.update');
        Route::delete('/categories/{category}', [AdminController::class, 'destroyCategory'])->name('categories.destroy');
        Route::post('/tickets/{ticket}/close', [AdminController::class, 'closeTicket'])->name('close');
    });
});
