<?php

namespace App\Domains\User\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $viewer = $request->user();
        $privacy = $this->privacy_settings ?? [];

        $showLastSeen = $this->shouldShowLastSeen($viewer, $privacy);

        return [
            'id'              => $this->id,
            'name'            => $this->name,
            'username'        => $this->username,
            'avatar'          => $this->avatar_url,
            'bio'             => $this->bio,
            'custom_status'   => $this->custom_status,
            'presence_status' => $this->presence_status,
            'last_seen_at'    => $showLastSeen ? $this->last_seen_at?->toISOString() : null,
            'is_blocked'      => $viewer ? $viewer->isBlocking($this->resource) : false,
            'is_muted'        => $viewer ? $viewer->isMuting($this->resource) : false,
        ];
    }

    private function shouldShowLastSeen($viewer, array $privacy): bool
    {
        $setting = $privacy['last_seen'] ?? 'everyone';

        return match($setting) {
            'everyone'  => true,
            'nobody'    => false,
            'contacts'  => false,
            default     => true,
        };
    }
}