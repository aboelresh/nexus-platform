<?php

use Illuminate\Support\Facades\Route;

Route::prefix('auth')->name('auth.')->group(function () {

    Route::post('/register', [\App\Domains\Auth\Controllers\RegisterController::class, 'register'])->name('register');
    Route::post('/login', [\App\Domains\Auth\Controllers\LoginController::class, 'login'])->name('login');
    Route::post('/verify-email/{id}/{hash}', [\App\Domains\Auth\Controllers\EmailVerificationController::class, 'verify'])->name('verify-email');
    Route::post('/forgot-password', [\App\Domains\Auth\Controllers\PasswordController::class, 'forgot'])->name('forgot-password');
    Route::post('/reset-password', [\App\Domains\Auth\Controllers\PasswordController::class, 'reset'])->name('reset-password');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [\App\Domains\Auth\Controllers\LoginController::class, 'logout'])->name('logout');
        Route::post('/verify-email/resend', [\App\Domains\Auth\Controllers\EmailVerificationController::class, 'resend'])->name('verify-email.resend');
        Route::post('/change-password', [\App\Domains\Auth\Controllers\PasswordController::class, 'change'])->name('change-password');
        Route::get('/me', [\App\Domains\Auth\Controllers\LoginController::class, 'me'])->name('me');
        Route::get('/sessions', [\App\Domains\Auth\Controllers\SessionController::class, 'index'])->name('sessions');
        Route::delete('/sessions/{tokenId}', [\App\Domains\Auth\Controllers\SessionController::class, 'destroy'])->name('sessions.destroy');
    });

});