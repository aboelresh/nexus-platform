<?php

use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('notifications')->name('notifications.')->group(function () {

    Route::get('/',           [\App\Domains\Notification\Controllers\NotificationController::class, 'index'])->name('index');
    Route::get('/stats',      [\App\Domains\Notification\Controllers\NotificationController::class, 'stats'])->name('stats');
    Route::get('/unread-count', [\App\Domains\Notification\Controllers\NotificationController::class, 'unreadCount'])->name('unread-count');
    Route::post('/read',      [\App\Domains\Notification\Controllers\NotificationController::class, 'markAsRead'])->name('read');
    Route::delete('/all',     [\App\Domains\Notification\Controllers\NotificationController::class, 'destroyAll'])->name('destroy-all');
    Route::delete('/{id}',    [\App\Domains\Notification\Controllers\NotificationController::class, 'destroy'])->name('destroy');
    Route::put('/preferences',[\App\Domains\Notification\Controllers\NotificationController::class, 'updatePreferences'])->name('preferences');

});