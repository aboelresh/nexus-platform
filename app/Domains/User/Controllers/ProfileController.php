<?php

namespace App\Domains\User\Controllers;

use App\Infrastructure\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return response()->json([
            'status' => true,
            'data'   => $request->user(),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        return response()->json([
            'status'  => true,
            'message' => 'قريباً.',
        ]);
    }

    public function uploadAvatar(Request $request): JsonResponse
    {
        return response()->json([
            'status'  => true,
            'message' => 'قريباً.',
        ]);
    }

    public function deleteAvatar(Request $request): JsonResponse
    {
        return response()->json([
            'status'  => true,
            'message' => 'قريباً.',
        ]);
    }

    public function updateStatus(Request $request): JsonResponse
    {
        return response()->json([
            'status'  => true,
            'message' => 'قريباً.',
        ]);
    }

    public function updatePrivacy(Request $request): JsonResponse
    {
        return response()->json([
            'status'  => true,
            'message' => 'قريباً.',
        ]);
    }
}