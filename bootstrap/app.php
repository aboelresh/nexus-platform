<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        using: function () {
            \Illuminate\Support\Facades\Route::middleware('web')
                ->group(base_path('routes/console-routes.php'));

            \Illuminate\Support\Facades\Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));
        },
        commands: __DIR__ . '/../routes/console.php',
        channels: __DIR__ . '/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
    $exceptions->render(function (\Throwable $e, Request $request) {
        if ($request->is('api/*') && !$request->is('console/*')) {
            return response()->json([
                'message' => $e->getMessage(),
                'status'  => false,
            ], method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500);
        }
    });
})->create();