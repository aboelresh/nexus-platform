<?php

namespace App\Domains\User\Services;

use App\Domains\User\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProfileService
{
    public function updateProfile(User $user, array $data): User
    {
        $user->update($data);
        return $user->fresh();
    }

    public function uploadAvatar(User $user, UploadedFile $file): User
    {
        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }

        $filename  = 'avatars/' . $user->id . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
        $path      = $file->storeAs('avatars', $user->id . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension(), 'public');

        $user->update(['avatar' => $path]);

        return $user->fresh();
    }

    public function deleteAvatar(User $user): User
    {
        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
            $user->update(['avatar' => null]);
        }

        return $user->fresh();
    }

    public function updateStatus(User $user, array $data): User
    {
        $user->update(array_filter($data, fn($v) => !is_null($v)));
        return $user->fresh();
    }

    public function updatePrivacy(User $user, array $data): User
    {
        $currentSettings = $user->privacy_settings ?? [];
        $newSettings     = array_merge($currentSettings, $data);

        $user->update(['privacy_settings' => $newSettings]);

        return $user->fresh();
    }
}