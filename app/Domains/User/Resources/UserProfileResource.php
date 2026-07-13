<?php

namespace App\Domains\User\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                       => $this->id,
            'name'                     => $this->name,
            'username'                 => $this->username,
            'email'                    => $this->email,
            'avatar'                   => $this->avatar_url,
            'bio'                      => $this->bio,
            'custom_status'            => $this->custom_status,
            'presence_status'          => $this->presence_status,
            'last_seen_at'             => $this->last_seen_at?->toISOString(),
            'phone'                    => $this->phone,
            'timezone'                 => $this->timezone,
            'locale'                   => $this->locale,
            'is_active'                => $this->is_active,
            'email_verified'           => !is_null($this->email_verified_at),
            'two_factor_enabled'       => !is_null($this->two_factor_confirmed_at),
            'privacy_settings'         => $this->privacy_settings ?? $this->defaultPrivacySettings(),
            'notification_preferences' => $this->notification_preferences ?? $this->defaultNotificationPreferences(),
            'created_at'               => $this->created_at->toISOString(),
        ];
    }

    private function defaultPrivacySettings(): array
    {
        return [
            'last_seen'       => 'everyone',
            'profile_photo'   => 'everyone',
            'bio'             => 'everyone',
            'groups'          => 'everyone',
            'direct_messages' => 'everyone',
        ];
    }

    private function defaultNotificationPreferences(): array
    {
        return [
            'email'    => true,
            'push'     => true,
            'in_app'   => true,
            'mentions' => true,
            'messages' => true,
            'groups'   => true,
            'calls'    => true,
        ];
    }
}