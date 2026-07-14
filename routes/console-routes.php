<?php

use App\Domains\DevConsole\Controllers\ConsoleController;
use Illuminate\Support\Facades\Route;

Route::prefix('devtools')
    ->middleware(\App\Infrastructure\Http\Middleware\DevConsoleMiddleware::class)
    ->group(function () {

    Route::get('/', [ConsoleController::class, 'index']);

    Route::prefix('api')->group(function () {
        Route::get('/health',        [ConsoleController::class, 'health']);
        Route::get('/environment',   [ConsoleController::class, 'environment']);
        Route::get('/performance',   [ConsoleController::class, 'performance']);
        Route::get('/database',      [ConsoleController::class, 'database']);
        Route::get('/queue',         [ConsoleController::class, 'queue']);
        Route::get('/redis',         [ConsoleController::class, 'redis']);
        Route::get('/logs',          [ConsoleController::class, 'logs']);
        Route::get('/storage',       [ConsoleController::class, 'storage']);
        Route::get('/security',      [ConsoleController::class, 'security']);
        Route::get('/doctor',        [ConsoleController::class, 'doctor']);
        Route::post('/retry-job',    [ConsoleController::class, 'retryJob']);
        Route::post('/flush-failed', [ConsoleController::class, 'flushFailed']);
    });
});