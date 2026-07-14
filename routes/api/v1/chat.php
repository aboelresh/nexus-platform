<?php

use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {

    Route::prefix('conversations')->name('conversations.')->group(function () {
        Route::get('/', [\App\Domains\Chat\Controllers\ConversationController::class, 'index'])->name('index');
        Route::post('/', [\App\Domains\Chat\Controllers\ConversationController::class, 'store'])->name('store');
        Route::get('/{id}', [\App\Domains\Chat\Controllers\ConversationController::class, 'show'])->name('show');
        Route::post('/{id}/read', [\App\Domains\Chat\Controllers\ConversationController::class, 'markAsRead'])->name('read');
        Route::post('/{id}/archive', [\App\Domains\Chat\Controllers\ConversationController::class, 'archive'])->name('archive');

        Route::prefix('/{conversationId}/messages')->name('messages.')->group(function () {
            Route::get('/', [\App\Domains\Chat\Controllers\MessageController::class, 'index'])->name('index');
            Route::post('/', [\App\Domains\Chat\Controllers\MessageController::class, 'store'])->name('store');
            Route::put('/{messageId}', [\App\Domains\Chat\Controllers\MessageController::class, 'update'])->name('update');
            Route::delete('/{messageId}', [\App\Domains\Chat\Controllers\MessageController::class, 'destroy'])->name('destroy');
            Route::post('/{messageId}/react', [\App\Domains\Chat\Controllers\MessageController::class, 'react'])->name('react');
            Route::post('/{messageId}/pin', [\App\Domains\Chat\Controllers\MessageController::class, 'pin'])->name('pin');
        });
    });

});