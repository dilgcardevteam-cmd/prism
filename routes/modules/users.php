<?php

use App\Modules\Users\Controllers\ChangePasswordController;
use App\Modules\Users\Controllers\ProfileController;
use App\Modules\Users\Controllers\UserImpersonationController;
use App\Modules\Users\Controllers\UserManagementController;
use Illuminate\Support\Facades\Route;

// Profile routes
Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

// Change password routes
Route::get('/change-password', [ChangePasswordController::class, 'show'])->name('password.show');
Route::put('/change-password', [ChangePasswordController::class, 'update'])->name('password.update');

Route::post('/stop-impersonating', [UserImpersonationController::class, 'stop'])
    ->middleware('throttle:10,1')
    ->name('users.impersonate.stop');

// User Management routes (superadmin only)
Route::middleware('superadmin')->group(function () {
    Route::resource('users', UserManagementController::class);
    Route::post('users/{user}/impersonate', [UserImpersonationController::class, 'start'])
        ->middleware('can:impersonate-user,user')
        ->name('users.impersonate.start');
    Route::put('users/{user}/block', [UserManagementController::class, 'block'])
        ->name('users.block');
    Route::put('users/{user}/access', [UserManagementController::class, 'updateAccess'])
        ->name('users.access.update');
});
