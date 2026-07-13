<?php

namespace App\Domains\User\Controllers;

use App\Infrastructure\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function show(Request $request, string $username): JsonResponse
    {
        return response()->json([
            'status'  => true,
            'message' => 'قريباً.',
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        return response()->json([
            'status'  => true,
            'message' => 'قريباً.',
        ]);
    }

    public function block(Request $request, string $username): JsonResponse
    {
        return response()->json([
            'status'  => true,
            'message' => 'قريباً.',
        ]);
    }

    public function unblock(Request $request, string $username): JsonResponse
    {
        return response()->json([
            'status'  => true,
            'message' => 'قريباً.',
        ]);
    }

    public function mute(Request $request, string $username): JsonResponse
    {
        return response()->json([
            'status'  => true,
            'message' => 'قريباً.',
        ]);
    }

    public function unmute(Request $request, string $username): JsonResponse
    {
        return response()->json([
            'status'  => true,
            'message' => 'قريباً.',
        ]);
    }
}