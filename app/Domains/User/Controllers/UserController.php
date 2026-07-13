<?php

namespace App\Domains\User\Controllers;

use App\Domains\User\Resources\UserResource;
use App\Domains\User\Services\UserService;
use App\Infrastructure\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(
        private UserService $userService
    ) {}

    public function show(Request $request, string $username): JsonResponse
    {
        $user = $this->userService->findByUsername($username);

        return response()->json([
            'status' => true,
            'data'   => new UserResource($user),
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q'        => ['required', 'string', 'min:2', 'max:100'],
            'per_page' => ['sometimes', 'integer', 'min:5', 'max:50'],
        ]);

        $results = $this->userService->search(
            $request->input('q'),
            $request->user(),
            $request->input('per_page', 20)
        );

        return response()->json([
            'status' => true,
            'data'   => UserResource::collection($results),
            'meta'   => [
                'current_page' => $results->currentPage(),
                'last_page'    => $results->lastPage(),
                'per_page'     => $results->perPage(),
                'total'        => $results->total(),
            ],
        ]);
    }

    public function block(Request $request, string $username): JsonResponse
    {
        $target = $this->userService->findByUsername($username);
        $this->userService->blockUser($request->user(), $target);

        return response()->json([
            'message' => 'تم حظر المستخدم بنجاح.',
            'status'  => true,
        ]);
    }

    public function unblock(Request $request, string $username): JsonResponse
    {
        $target = $this->userService->findByUsername($username);
        $this->userService->unblockUser($request->user(), $target);

        return response()->json([
            'message' => 'تم إلغاء حظر المستخدم بنجاح.',
            'status'  => true,
        ]);
    }

    public function mute(Request $request, string $username): JsonResponse
    {
        $request->validate([
            'minutes' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:10080'],
        ]);

        $target = $this->userService->findByUsername($username);
        $this->userService->muteUser($request->user(), $target, $request->input('minutes'));

        return response()->json([
            'message' => 'تم كتم المستخدم بنجاح.',
            'status'  => true,
        ]);
    }

    public function unmute(Request $request, string $username): JsonResponse
    {
        $target = $this->userService->findByUsername($username);
        $this->userService->unmuteUser($request->user(), $target);

        return response()->json([
            'message' => 'تم إلغاء كتم المستخدم بنجاح.',
            'status'  => true,
        ]);
    }
}