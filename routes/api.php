<?php

use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json([
        'status'  => 'ok',
        'service' => 'NexusPlatform API',
        'version' => '1.0.0',
        'timestamp' => now()->toISOString(),
    ]);
});

Route::prefix('v1')->name('v1.')->group(function () {

    require __DIR__ . '/api/v1/auth.php';
    require __DIR__ . '/api/v1/users.php';

});