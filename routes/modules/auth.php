<?php

use App\Modules\Auth\Controllers\ForgotPasswordController;
use App\Modules\Auth\Controllers\LoginController;
use App\Modules\Auth\Controllers\RegisterController;
use App\Modules\Auth\Controllers\VerificationController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Auth::routes(['reset' => false, 'register' => false]); // Disable default register routes

// Custom register routes
Route::get('register', [RegisterController::class, 'showRegistrationForm'])->name('register');
Route::post('register', [RegisterController::class, 'register']);

// Email verification routes - Token route FIRST (more specific)
Route::get('/email/verify/token/{token}', [VerificationController::class, 'verifyWithToken'])->name('verification.verify.token');
// Then general verification routes
Route::get('/email/verify', [VerificationController::class, 'show'])->name('verification.notice');
Route::get('/email/verify/{id}/{hash}', [VerificationController::class, 'verify'])->middleware(['signed'])->name('verification.verify');
Route::post('/email/resend', [VerificationController::class, 'resend'])->middleware(['throttle:6,1'])->name('verification.resend');

Route::get('/forgot-password', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('forgot-password');
Route::post('/forgot-password', [ForgotPasswordController::class, 'sendOtp'])->name('forgot-password.send-otp');
Route::get('/verify-otp', [ForgotPasswordController::class, 'showVerifyOtpForm'])->name('forgot-password.verify');
Route::post('/verify-otp', [ForgotPasswordController::class, 'verifyOtp'])->name('forgot-password.verify-otp');
Route::get('/reset-password', [ForgotPasswordController::class, 'showResetForm'])->name('forgot-password.reset');
Route::post('/reset-password', [ForgotPasswordController::class, 'resetPassword'])->name('forgot-password.reset-submit');

Route::get('/admin/login', [LoginController::class, 'showMaintenanceLoginForm'])
    ->name('maintenance.superadmin-login');

