<?php

namespace App\Domains\DevConsole\Controllers;

use App\Domains\DevConsole\Services\DiagnosticsService;
use App\Infrastructure\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConsoleController extends Controller
{
    public function __construct(
        private DiagnosticsService $diagnostics
    ) {}

    public function index(): \Illuminate\Http\Response
    {
        return response(file_get_contents(public_path('console/index.html')));
    }

    public function health(): JsonResponse
    {
        return response()->json($this->diagnostics->getHealthCheck());
    }

    public function environment(): JsonResponse
    {
        return response()->json($this->diagnostics->getEnvironment());
    }

    public function performance(): JsonResponse
    {
        return response()->json($this->diagnostics->getPerformance());
    }

    public function database(): JsonResponse
    {
        return response()->json($this->diagnostics->getDatabaseStats());
    }

    public function queue(): JsonResponse
    {
        return response()->json($this->diagnostics->getQueueStats());
    }

    public function redis(): JsonResponse
    {
        return response()->json($this->diagnostics->getRedisStats());
    }

    public function logs(Request $request): JsonResponse
    {
        $level = $request->input('level', 'all');
        $limit = (int) $request->input('limit', 50);
        return response()->json($this->diagnostics->getLogs($level, $limit));
    }

    public function storage(): JsonResponse
    {
        return response()->json($this->diagnostics->getStorageStats());
    }

    public function security(): JsonResponse
    {
        return response()->json($this->diagnostics->getSecurityStats());
    }

    public function doctor(): JsonResponse
    {
        return response()->json($this->diagnostics->getSystemDoctor());
    }

    public function retryJob(Request $request): JsonResponse
    {
        $uuid = $request->input('uuid');
        \Illuminate\Support\Facades\Artisan::call('queue:retry', ['id' => [$uuid]]);
        return response()->json(['status' => true, 'message' => 'Job queued for retry']);
    }

    public function flushFailed(): JsonResponse
    {
        \Illuminate\Support\Facades\Artisan::call('queue:flush');
        return response()->json(['status' => true, 'message' => 'Failed jobs cleared']);
    }
}