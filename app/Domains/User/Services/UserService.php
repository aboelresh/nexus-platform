<?php

namespace App\Domains\User\Services;

use App\Domains\User\Models\User;
use Illuminate\Validation\ValidationException;

class UserService
{
    public function findByUsername(string $username): User
    {
        return User::where('username', $username)->firstOrFail();
    }

    public function search(string $query, User $viewer, int $perPage = 20): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return User::where(function ($q) use ($query) {
            $q->where('name', 'LIKE', "%{$query}%")
              ->orWhere('username', 'LIKE', "%{$query}%");
        })
        ->where('id', '!=', $viewer->id)
        ->where('is_active', true)
        ->where('is_banned', false)
        ->whereNull('deleted_at')
        ->whereNotIn('id', $viewer->blockedByUsers()->pluck('blocker_id'))
        ->select(['id', 'name', 'username', 'avatar', 'bio', 'custom_status', 'presence_status', 'privacy_settings'])
        ->paginate($perPage);
    }

    public function blockUser(User $blocker, User $target): void
    {
        if ($blocker->id === $target->id) {
            throw ValidationException::withMessages([
                'user' => ['لا يمكنك حظر نفسك.'],
            ]);
        }

        if ($blocker->isBlocking($target)) {
            throw ValidationException::withMessages([
                'user' => ['المستخدم محظور بالفعل.'],
            ]);
        }

        $blocker->blockedUsers()->attach($target->id);
    }

    public function unblockUser(User $blocker, User $target): void
    {
        if (!$blocker->isBlocking($target)) {
            throw ValidationException::withMessages([
                'user' => ['المستخدم غير محظور.'],
            ]);
        }

        $blocker->blockedUsers()->detach($target->id);
    }

    public function muteUser(User $muter, User $target, ?int $minutes = null): void
    {
        if ($muter->id === $target->id) {
            throw ValidationException::withMessages([
                'user' => ['لا يمكنك كتم نفسك.'],
            ]);
        }

        $expiresAt = $minutes ? now()->addMinutes($minutes) : null;

        $muter->mutedUsers()->syncWithoutDetaching([
            $target->id => ['expires_at' => $expiresAt],
        ]);
    }

    public function unmuteUser(User $muter, User $target): void
    {
        $muter->mutedUsers()->detach($target->id);
    }
}