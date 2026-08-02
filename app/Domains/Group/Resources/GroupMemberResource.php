<?php

namespace App\Domains\Group\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GroupMemberResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'user'       => [
                'id'              => $this->user_id,
                'name'            => $this->relationLoaded('user') ? $this->user?->name : null,
                'username'        => $this->relationLoaded('user') ? $this->user?->username : null,
                'avatar'          => $this->relationLoaded('user') ? $this->user?->avatar_url : null,
                'presence_status' => $this->relationLoaded('user') ? $this->user?->presence_status : null,
            ],
            'role'       => $this->role,
            'is_muted'   => $this->isMuted(),
            'is_banned'  => $this->isBanned(),
            'joined_at'  => $this->joined_at?->toISOString(),
            'invited_by' => $this->relationLoaded('invitedBy') && $this->invited_by ? [
                'id'   => $this->invitedBy?->id,
                'name' => $this->invitedBy?->name,
            ] : null,
        ];
    }
}