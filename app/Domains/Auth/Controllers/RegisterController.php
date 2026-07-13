<?php

namespace App\Domains\Auth\Controllers;

use App\Domains\Auth\Requests\RegisterRequest;
use App\Domains\Auth\Resources\AuthUserResource;
use App\Domains\Auth\Services\AuthService;
use App\Infrastructure\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class RegisterController extends Controller
{
    public function __construct(
        private AuthService $authService
    ) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register($request->validated());

        return response()->json([
            'message' => 'تم إنشاء الحساب بنجاح. يرجى التحقق من بريدك الإلكتروني.',
            'status'  => true,
            'data'    => [
                'user'  => new AuthUserResource($result['user']),
                'token' => $result['token'],
            ],
        ], 201);
    }
}