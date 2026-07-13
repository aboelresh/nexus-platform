<?php

namespace App\Domains\User\Controllers;

use App\Domains\User\Requests\UpdatePrivacyRequest;
use App\Domains\User\Requests\UpdateProfileRequest;
use App\Domains\User\Requests\UpdateStatusRequest;
use App\Domains\User\Resources\UserProfileResource;
use App\Domains\User\Services\ProfileService;
use App\Infrastructure\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function __construct(
        private ProfileService $profileService
    ) {}

    public function show(Request $request): JsonResponse
    {
        return response()->json([
            'status' => true,
            'data'   => new UserProfileResource($request->user()),
        ]);
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $this->profileService->updateProfile($request->user(), $request->validated());

        return response()->json([
            'message' => 'تم تحديث الملف الشخصي بنجاح.',
            'status'  => true,
            'data'    => new UserProfileResource($user),
        ]);
    }

    public function uploadAvatar(Request $request): JsonResponse
    {
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:5120'],
        ]);

        $user = $this->profileService->uploadAvatar($request->user(), $request->file('avatar'));

        return response()->json([
            'message' => 'تم رفع الصورة الشخصية بنجاح.',
            'status'  => true,
            'data'    => ['avatar' => $user->avatar_url],
        ]);
    }

    public function deleteAvatar(Request $request): JsonResponse
    {
        $this->profileService->deleteAvatar($request->user());

        return response()->json([
            'message' => 'تم حذف الصورة الشخصية بنجاح.',
            'status'  => true,
        ]);
    }

    public function updateStatus(UpdateStatusRequest $request): JsonResponse
    {
        $user = $this->profileService->updateStatus($request->user(), $request->validated());

        return response()->json([
            'message' => 'تم تحديث الحالة بنجاح.',
            'status'  => true,
            'data'    => [
                'custom_status'   => $user->custom_status,
                'presence_status' => $user->presence_status,
            ],
        ]);
    }

    public function updatePrivacy(UpdatePrivacyRequest $request): JsonResponse
    {
        $user = $this->profileService->updatePrivacy($request->user(), $request->validated());

        return response()->json([
            'message' => 'تم تحديث إعدادات الخصوصية بنجاح.',
            'status'  => true,
            'data'    => ['privacy_settings' => $user->privacy_settings],
        ]);
    }
}