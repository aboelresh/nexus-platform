<?php

namespace App\Domains\Auth\Controllers;

use App\Domains\Auth\Requests\ChangePasswordRequest;
use App\Domains\Auth\Services\AuthService;
use App\Infrastructure\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class PasswordController extends Controller
{
    public function __construct(
        private AuthService $authService
    ) {}

    public function forgot(Request $request): JsonResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        $status = $this->authService->sendPasswordResetLink($request->email);

        return response()->json([
            'message' => __($status),
            'status'  => $status === Password::RESET_LINK_SENT,
        ]);
    }

    public function reset(Request $request): JsonResponse
    {
        $request->validate([
            'token'    => ['required'],
            'email'    => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $status = $this->authService->resetPassword($request->only('email', 'password', 'password_confirmation', 'token'));

        return response()->json([
            'message' => __($status),
            'status'  => $status === Password::PASSWORD_RESET,
        ], $status === Password::PASSWORD_RESET ? 200 : 422);
    }

    public function change(ChangePasswordRequest $request): JsonResponse
    {
        $this->authService->changePassword(
            $request->user(),
            $request->input('current_password'),
            $request->input('password')
        );

        return response()->json([
            'message' => 'تم تغيير كلمة المرور بنجاح.',
            'status'  => true,
        ]);
    }
}