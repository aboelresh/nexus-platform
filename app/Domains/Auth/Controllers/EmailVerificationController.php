<?php

namespace App\Domains\Auth\Controllers;

use App\Domains\Auth\Services\AuthService;
use App\Infrastructure\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    public function __construct(
        private AuthService $authService
    ) {}

    public function verify(Request $request, string $id, string $hash): JsonResponse
    {
        $user = \App\Domains\User\Models\User::findOrFail($id);

        $verified = $this->authService->verifyEmail($user, $hash);

        return response()->json([
            'message' => $verified ? 'تم التحقق من البريد الإلكتروني بنجاح.' : 'رابط التحقق غير صالح.',
            'status'  => $verified,
        ], $verified ? 200 : 400);
    }

    public function resend(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'البريد الإلكتروني محقق بالفعل.',
                'status'  => false,
            ], 422);
        }

        $user->sendEmailVerificationNotification();

        return response()->json([
            'message' => 'تم إرسال رابط التحقق مجدداً.',
            'status'  => true,
        ]);
    }
}