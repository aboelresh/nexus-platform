<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;

Route::post('/broadcasting/auth', function (Request $request) {
    return Broadcast::auth($request);
})->middleware('auth:sanctum');

Route::get('/ping', function () {
    return response()->json([
        'status'    => 'ok',
        'service'   => 'NexusPlatform API',
        'version'   => '1.0.0',
        'timestamp' => now()->toISOString(),
    ]);
});

Route::prefix('v1')->name('v1.')->group(function () {
    require __DIR__ . '/api/v1/auth.php';
    require __DIR__ . '/api/v1/users.php';
    require __DIR__ . '/api/v1/chat.php';
    require __DIR__ . '/api/v1/media.php';
    require __DIR__ . '/api/v1/groups.php';
});