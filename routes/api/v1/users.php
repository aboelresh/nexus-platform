<?php

use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('users')->name('users.')->group(function () {

    Route::get('/profile', [\App\Domains\User\Controllers\ProfileController::class, 'show'])->name('profile.show');
    Route::put('/profile', [\App\Domains\User\Controllers\ProfileController::class, 'update'])->name('profile.update');
    Route::post('/avatar', [\App\Domains\User\Controllers\ProfileController::class, 'uploadAvatar'])->name('profile.avatar');
    Route::delete('/avatar', [\App\Domains\User\Controllers\ProfileController::class, 'deleteAvatar'])->name('profile.avatar.delete');
    Route::put('/status', [\App\Domains\User\Controllers\ProfileController::class, 'updateStatus'])->name('profile.status');
    Route::put('/privacy', [\App\Domains\User\Controllers\ProfileController::class, 'updatePrivacy'])->name('profile.privacy');

    Route::get('/search', [\App\Domains\User\Controllers\UserController::class, 'search'])->name('search');
    Route::get('/{username}', [\App\Domains\User\Controllers\UserController::class, 'show'])->name('show');
    Route::post('/{username}/block', [\App\Domains\User\Controllers\UserController::class, 'block'])->name('block');
    Route::delete('/{username}/block', [\App\Domains\User\Controllers\UserController::class, 'unblock'])->name('unblock');
    Route::post('/{username}/mute', [\App\Domains\User\Controllers\UserController::class, 'mute'])->name('mute');
    Route::delete('/{username}/mute', [\App\Domains\User\Controllers\UserController::class, 'unmute'])->name('unmute');

});