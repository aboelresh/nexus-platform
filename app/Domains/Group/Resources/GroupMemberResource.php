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
                'id'              => $this->user->id,
                'name'            => $this->user->name,
                'username'        => $this->user->username,
                'avatar'          => $this->user->avatar_url,
                'presence_status' => $this->user->presence_status,
            ],
            'role'       => $this->role,
            'is_muted'   => $this->isMuted(),
            'is_banned'  => $this->isBanned(),
            'joined_at'  => $this->joined_at?->toISOString(),
            'invited_by' => $this->invited_by ? [
                'id'   => $this->invitedBy?->id,
                'name' => $this->invitedBy?->name,
            ] : null,
        ];
    }
}