<?php

namespace App\Domains\Auth\Controllers;

use App\Domains\Auth\Resources\AuthUserResource;
use App\Domains\Auth\Services\AuthService;
use App\Domains\Auth\Requests\LoginRequest;
use App\Infrastructure\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    public function __construct(
        private AuthService $authService
    ) {}

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login($request->validated());

        return response()->json([
            'message' => 'تم تسجيل الدخول بنجاح.',
            'status'  => true,
            'data'    => [
                'user'  => new AuthUserResource($result['user']),
                'token' => $result['token'],
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $allDevices = $request->boolean('all_devices', false);
        $this->authService->logout($request->user(), $allDevices);

        return response()->json([
            'message' => 'تم تسجيل الخروج بنجاح.',
            'status'  => true,
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'status' => true,
            'data'   => new AuthUserResource($request->user()),
        ]);
    }
}