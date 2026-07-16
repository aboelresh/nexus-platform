<?php

use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {

    Route::prefix('groups')->name('groups.')->group(function () {

        Route::get('/search', [\App\Domains\Group\Controllers\GroupController::class, 'search'])->name('search');
        Route::get('/', [\App\Domains\Group\Controllers\GroupController::class, 'index'])->name('index');
        Route::post('/', [\App\Domains\Group\Controllers\GroupController::class, 'store'])->name('store');
        Route::get('/{id}', [\App\Domains\Group\Controllers\GroupController::class, 'show'])->name('show');
        Route::put('/{id}', [\App\Domains\Group\Controllers\GroupController::class, 'update'])->name('update');
        Route::delete('/{id}', [\App\Domains\Group\Controllers\GroupController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/join', [\App\Domains\Group\Controllers\GroupController::class, 'join'])->name('join');
        Route::post('/{id}/leave', [\App\Domains\Group\Controllers\GroupController::class, 'leave'])->name('leave');
        Route::post('/{id}/transfer-ownership', [\App\Domains\Group\Controllers\GroupController::class, 'transferOwnership'])->name('transfer');

        Route::prefix('/{groupId}/members')->name('members.')->group(function () {
            Route::get('/', [\App\Domains\Group\Controllers\GroupMemberController::class, 'index'])->name('index');
            Route::put('/{userId}/role', [\App\Domains\Group\Controllers\GroupMemberController::class, 'changeRole'])->name('role');
            Route::delete('/{userId}/kick', [\App\Domains\Group\Controllers\GroupMemberController::class, 'kick'])->name('kick');
            Route::post('/{userId}/ban', [\App\Domains\Group\Controllers\GroupMemberController::class, 'ban'])->name('ban');
            Route::delete('/{userId}/ban', [\App\Domains\Group\Controllers\GroupMemberController::class, 'unban'])->name('unban');
            Route::post('/{userId}/mute', [\App\Domains\Group\Controllers\GroupMemberController::class, 'mute'])->name('mute');
            Route::delete('/{userId}/mute', [\App\Domains\Group\Controllers\GroupMemberController::class, 'unmute'])->name('unmute');
        });

        Route::prefix('/{groupId}/join-requests')->name('join-requests.')->group(function () {
            Route::get('/', [\App\Domains\Group\Controllers\GroupMemberController::class, 'joinRequests'])->name('index');
            Route::put('/{requestId}', [\App\Domains\Group\Controllers\GroupMemberController::class, 'reviewRequest'])->name('review');
        });

        Route::post('/{groupId}/invite', [\App\Domains\Group\Controllers\GroupInvitationController::class, 'invite'])->name('invite');
    });

    Route::prefix('invitations')->name('invitations.')->group(function () {
        Route::get('/', [\App\Domains\Group\Controllers\GroupInvitationController::class, 'myInvitations'])->name('index');
        Route::post('/accept', [\App\Domains\Group\Controllers\GroupInvitationController::class, 'accept'])->name('accept');
        Route::post('/decline', [\App\Domains\Group\Controllers\GroupInvitationController::class, 'decline'])->name('decline');
    });

});