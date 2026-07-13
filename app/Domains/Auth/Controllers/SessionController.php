<?php

namespace App\Domains\Auth\Controllers;

use App\Domains\Auth\Services\TokenService;
use App\Infrastructure\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SessionController extends Controller
{
    public function __construct(
        private TokenService $tokenService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tokens = $this->tokenService->getUserTokens($request->user());

        $currentTokenId = $request->user()->currentAccessToken()->id;

        $sessions = $tokens->map(fn($token) => [
            'id'         => $token->id,
            'name'       => $token->name,
            'last_used'  => $token->last_used_at?->diffForHumans(),
            'created_at' => $token->created_at->toISOString(),
            'expires_at' => $token->expires_at?->toISOString(),
            'is_current' => $token->id === $currentTokenId,
        ]);

        return response()->json([
            'status' => true,
            'data'   => $sessions,
        ]);
    }

    public function destroy(Request $request, int $tokenId): JsonResponse
    {
        $token = $request->user()->tokens()->findOrFail($tokenId);
        $this->tokenService->revokeToken($token);

        return response()->json([
            'message' => 'تم إنهاء الجلسة بنجاح.',
            'status'  => true,
        ]);
    }
}