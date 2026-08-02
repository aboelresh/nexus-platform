<?php

use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('calls')->name('calls.')->group(function () {

    Route::post('/initiate',                     [\App\Domains\Call\Controllers\CallController::class, 'initiate'])->name('initiate');
    Route::get('/history',                       [\App\Domains\Call\Controllers\CallController::class, 'history'])->name('history');
    Route::get('/active/{conversationId}',       [\App\Domains\Call\Controllers\CallController::class, 'active'])->name('active');
    Route::post('/{callId}/answer',              [\App\Domains\Call\Controllers\CallController::class, 'answer'])->name('answer');
    Route::post('/{callId}/reject',              [\App\Domains\Call\Controllers\CallController::class, 'reject'])->name('reject');
    Route::post('/{callId}/end',                 [\App\Domains\Call\Controllers\CallController::class, 'end'])->name('end');
    Route::post('/{callId}/signal',              [\App\Domains\Call\Controllers\CallController::class, 'signal'])->name('signal');
    Route::post('/{callId}/toggle-mute',         [\App\Domains\Call\Controllers\CallController::class, 'toggleMute'])->name('toggle-mute');
    Route::post('/{callId}/toggle-camera',       [\App\Domains\Call\Controllers\CallController::class, 'toggleCamera'])->name('toggle-camera');

});