<?php

namespace App\Infrastructure\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DevConsoleMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!app()->isLocal() && !app()->environment('staging')) {
            abort(404);
        }

        $token = $request->header('X-Console-Token') 
              ?? $request->query('token');

        if ($token !== config('app.console_token', 'nexus-dev-console-2026')) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}