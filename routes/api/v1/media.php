<?php

use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {

    Route::prefix('media')->name('media.')->group(function () {
        Route::post('/upload', [\App\Domains\Media\Controllers\MediaController::class, 'upload'])->name('upload');
        Route::get('/{mediaId}', [\App\Domains\Media\Controllers\MediaController::class, 'show'])->name('show');
        Route::delete('/{mediaId}', [\App\Domains\Media\Controllers\MediaController::class, 'destroy'])->name('destroy');
        Route::get('/conversation/{conversationId}', [\App\Domains\Media\Controllers\MediaController::class, 'conversationMedia'])->name('conversation');
    });

});