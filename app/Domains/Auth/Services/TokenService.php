<?php

namespace App\Domains\Auth\Services;

use App\Domains\User\Models\User;
use Laravel\Sanctum\PersonalAccessToken;

class TokenService
{
    const ABILITIES = [
        'access-api',
        'read:profile',
        'write:profile',
        'read:messages',
        'write:messages',
    ];

    public function createToken(User $user, string $deviceName, array $abilities = ['*']): string
    {
        return $user->createToken($deviceName, $abilities, now()->addDays(30))->plainTextToken;
    }

    public function revokeToken(PersonalAccessToken $token): void
    {
        $token->delete();
    }

    public function revokeAllTokens(User $user): void
    {
        $user->tokens()->delete();
    }

    public function revokeOtherTokens(User $user, int $currentTokenId): void
    {
        $user->tokens()->where('id', '!=', $currentTokenId)->delete();
    }

    public function getUserTokens(User $user): \Illuminate\Database\Eloquent\Collection
    {
        return $user->tokens()->latest()->get();
    }
}