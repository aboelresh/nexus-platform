<?php

namespace App\Domains\Auth\Services;

use App\Domains\Presence\Events\UserPresenceUpdated;
use App\Domains\User\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function __construct(
        private TokenService $tokenService
    ) {}

    public function register(array $data): array
    {
        $user = User::create([
            'name'     => $data['name'],
            'username' => $data['username'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
            'timezone' => $data['timezone'] ?? 'UTC',
            'locale'   => $data['locale'] ?? 'en',
        ]);

        event(new Registered($user));

        $token = $this->tokenService->createToken($user, $data['device_name'] ?? 'default');

        return compact('user', 'token');
    }

    public function login(array $data): array
    {
        $user = User::where('email', $data['email'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['بيانات الدخول غير صحيحة.'],
            ]);
        }

        if ($user->is_banned) {
            throw ValidationException::withMessages([
                'email' => ['تم حظر هذا الحساب. السبب: ' . $user->ban_reason],
            ]);
        }

        if (!$user->is_active) {
            throw ValidationException::withMessages([
                'email' => ['هذا الحساب غير نشط.'],
            ]);
        }

        $token = $this->tokenService->createToken($user, $data['device_name'] ?? 'default');

        $user->update(['presence_status' => 'online']);

        broadcast(new UserPresenceUpdated($user->fresh(), 'online'));

        return compact('user', 'token');
    }

    public function logout(User $user, bool $allDevices = false): void
    {
        if ($allDevices) {
            $this->tokenService->revokeAllTokens($user);
        } else {
            $user->currentAccessToken()->delete();
        }

        $user->update([
            'presence_status' => 'offline',
            'last_seen_at'    => now(),
        ]);

        broadcast(new UserPresenceUpdated($user->fresh(), 'offline'));
    }

    public function verifyEmail(User $user, string $hash): bool
    {
        if (!hash_equals(sha1($user->getEmailForVerification()), $hash)) {
            return false;
        }

        if ($user->hasVerifiedEmail()) {
            return true;
        }

        $user->markEmailAsVerified();
        event(new Verified($user));

        return true;
    }

    public function sendPasswordResetLink(string $email): string
    {
        return Password::sendResetLink(['email' => $email]);
    }

    public function resetPassword(array $data): string
    {
        $status = Password::reset($data, function (User $user, string $password) {
            $user->forceFill([
                'password'       => Hash::make($password),
                'remember_token' => Str::random(60),
            ])->save();

            $this->tokenService->revokeAllTokens($user);
        });

        return $status;
    }

    public function changePassword(User $user, string $currentPassword, string $newPassword): void
    {
        if (!Hash::check($currentPassword, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['كلمة المرور الحالية غير صحيحة.'],
            ]);
        }

        $user->update([
            'password' => Hash::make($newPassword),
        ]);

        $this->tokenService->revokeOtherTokens($user, $user->currentAccessToken()->id);
    }
}